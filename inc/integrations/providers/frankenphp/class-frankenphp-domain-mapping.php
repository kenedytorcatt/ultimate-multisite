<?php
/**
 * FrankenPHP Domain Mapping Capability.
 *
 * Uses Caddy's on-demand TLS for automatic Let's Encrypt certificates.
 * When a domain is added via Ultimate Multisite, this module pre-provisions
 * the certificate via Caddy's admin API so the first visitor doesn't wait.
 *
 * Caddy's on-demand TLS calls an "ask" endpoint to validate domains before
 * issuing certificates. This module registers a lightweight REST endpoint
 * that checks the WordPress multisite domain tables.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/FrankenPHP
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\FrankenPHP;

use WP_Ultimo\Integrations\Base_Capability_Module;
use WP_Ultimo\Integrations\Capabilities\Domain_Mapping_Capability;

defined('ABSPATH') || exit;

/**
 * FrankenPHP domain mapping capability module.
 *
 * @since 2.5.0
 */
class FrankenPHP_Domain_Mapping extends Base_Capability_Module implements Domain_Mapping_Capability {

    /**
     * Supported features.
     *
     * @var array
     */
    protected array $supported_features = ['autossl'];

    /**
     * {@inheritdoc}
     */
    public function get_capability_id(): string {

        return 'domain-mapping';
    }

    /**
     * {@inheritdoc}
     */
    public function get_title(): string {

        return __('Domain Mapping', 'ultimate-multisite');
    }

    /**
     * {@inheritdoc}
     */
    public function get_explainer_lines(): array {

        return [
            'will'     => [
                __('Automatically provision Let\'s Encrypt SSL certificates for mapped domains via Caddy.', 'ultimate-multisite'),
                __('Validate domain ownership via an internal "ask" endpoint before issuing certificates.', 'ultimate-multisite'),
            ],
            'will_not' => [
                __('This integration does not manage DNS records. Domains must already point to this server.', 'ultimate-multisite'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void {

        add_action('wu_add_domain', [$this, 'on_add_domain'], 10, 2);
        add_action('wu_remove_domain', [$this, 'on_remove_domain'], 10, 2);
        add_action('wu_add_subdomain', [$this, 'on_add_subdomain'], 10, 2);
        add_action('wu_remove_subdomain', [$this, 'on_remove_subdomain'], 10, 2);

        // Register the "ask" endpoint for Caddy's on-demand TLS validation.
        add_action('rest_api_init', [$this, 'register_ask_endpoint']);
    }

    /**
     * Register the REST endpoint that Caddy calls to validate domains
     * before issuing on-demand TLS certificates.
     *
     * @return void
     */
    public function register_ask_endpoint(): void {

        register_rest_route('wu-caddy/v1', '/ask', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'handle_ask_request'],
            'permission_callback' => [$this, 'validate_ask_request'],
        ]);
    }

    /**
     * Only allow requests from localhost (Caddy admin API).
     *
     * @param \WP_REST_Request $request The request.
     * @return bool
     */
    public function validate_ask_request(\WP_REST_Request $request): bool {

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return in_array($ip, ['127.0.0.1', '::1', ''], true);
    }

    /**
     * Handle the "ask" request from Caddy's on-demand TLS.
     *
     * Caddy sends GET /wp-json/wu-caddy/v1/ask?domain=example.com
     * Return 200 if the domain is valid, 403 otherwise.
     *
     * @param \WP_REST_Request $request The request.
     * @return \WP_REST_Response
     */
    public function handle_ask_request(\WP_REST_Request $request): \WP_REST_Response {

        $domain = sanitize_text_field($request->get_param('domain'));

        if (empty($domain)) {
            return new \WP_REST_Response(['error' => 'missing domain'], 400);
        }

        if ($this->is_valid_multisite_domain($domain)) {
            return new \WP_REST_Response(['ok' => true], 200);
        }

        return new \WP_REST_Response(['error' => 'unknown domain'], 403);
    }

    /**
     * Check if a domain belongs to this WordPress multisite.
     *
     * Checks both the wp_blogs table (subdomains) and the Ultimate Multisite
     * domain mapping table.
     *
     * @param string $domain The domain to check.
     * @return bool
     */
    private function is_valid_multisite_domain(string $domain): bool {

        global $wpdb;

        // Check wp_blogs table (covers subdomains and primary domains).
        $blog_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = %s LIMIT 1",
                $domain
            )
        );

        if ($blog_id) {
            return true;
        }

        // Check Ultimate Multisite domain mapping table.
        $table = $wpdb->base_prefix . 'wu_domain_mappings';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            $mapping_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE domain = %s AND active = 1 LIMIT 1",
                    $domain
                )
            );

            if ($mapping_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Called when a new domain is mapped.
     *
     * Pre-provisions a Let's Encrypt certificate via Caddy's admin API
     * so the first visitor doesn't experience a TLS handshake delay.
     *
     * @param string $domain  The domain name being mapped.
     * @param int    $site_id ID of the site receiving the mapping.
     * On-demand TLS handles certificate provisioning automatically on first
     * TLS handshake. The ask endpoint validates the domain. No explicit
     * provisioning needed — just log for observability.
     *
     * @return void
     */
    public function on_add_domain(string $domain, int $site_id): void {

        if (function_exists('wu_log_add')) {
            wu_log_add('integration-frankenphp', "Domain added: {$domain} (cert will be provisioned on first visit via on-demand TLS)");
        }
    }

    /**
     * {@inheritdoc}
     *
     * Caddy automatically stops renewing certs for domains that fail the
     * ask endpoint check, so no explicit cleanup is needed.
     */
    public function on_remove_domain(string $domain, int $site_id): void {

        if (function_exists('wu_log_add')) {
            wu_log_add('integration-frankenphp', "Domain removed: {$domain} (cert will expire naturally)");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function on_add_subdomain(string $subdomain, int $site_id): void {
    }

    /**
     * {@inheritdoc}
     */
    public function on_remove_subdomain(string $subdomain, int $site_id): void {
    }

    /**
     * {@inheritdoc}
     */
    public function test_connection() {

        return $this->get_integration()->test_connection();
    }
}
