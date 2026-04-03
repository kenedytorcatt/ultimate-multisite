<?php
/**
 * FrankenPHP Integration.
 *
 * Provides automatic SSL via Caddy's on-demand TLS and admin API.
 * No external credentials needed — communicates with the local Caddy
 * admin API at localhost:2019.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/FrankenPHP
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\FrankenPHP;

use WP_Ultimo\Integrations\Integration;

defined('ABSPATH') || exit;

/**
 * FrankenPHP integration provider.
 *
 * @since 2.5.0
 */
class FrankenPHP_Integration extends Integration {

    /**
     * Caddy admin API base URL.
     *
     * @var string
     */
    private string $admin_api = 'http://localhost:2019';

    /**
     * Constructor.
     */
    public function __construct() {

        parent::__construct('frankenphp', 'FrankenPHP');

        $this->set_description(
            __('FrankenPHP (Caddy) integration with automatic Let\'s Encrypt SSL for mapped domains.', 'ultimate-multisite')
        );
        $this->set_logo(function_exists('wu_get_asset') ? wu_get_asset('frankenphp.svg', 'img/hosts') : '');
        $this->set_supports(['autossl', 'no-instructions', 'no-config']);
    }

    /**
     * Auto-detect FrankenPHP by checking for the FRANKENPHP_WORKER constant
     * or the frankenphp SAPI name.
     *
     * @return bool
     */
    public function detect(): bool {

        // FrankenPHP sets this in worker mode.
        if (defined('FRANKENPHP_WORKER') || php_sapi_name() === 'frankenphp') {
            return true;
        }

        // Fallback: check if Caddy admin API is reachable.
        $response = wp_remote_get($this->admin_api . '/config/', [
            'timeout'   => 2,
            'sslverify' => false,
        ]);

        return ! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Test connection to the Caddy admin API.
     *
     * @return true|\WP_Error
     */
    public function test_connection() {

        $response = wp_remote_get($this->admin_api . '/config/', [
            'timeout'   => 5,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new \WP_Error(
                'caddy_api_error',
                sprintf(__('Caddy admin API returned HTTP %d', 'ultimate-multisite'), $code)
            );
        }

        return true;
    }

    /**
     * Get the Caddy admin API base URL.
     *
     * @return string
     */
    public function get_admin_api(): string {

        return $this->admin_api;
    }

    /**
     * Send a request to the Caddy admin API.
     *
     * @param string $endpoint API endpoint path.
     * @param array  $body     Request body (will be JSON-encoded).
     * @param string $method   HTTP method.
     * @return array|\WP_Error Decoded response or error.
     */
    public function api_call(string $endpoint, array $body = [], string $method = 'POST') {

        $args = [
            'method'    => $method,
            'timeout'   => 30,
            'sslverify' => false,
            'headers'   => ['Content-Type' => 'application/json'],
        ];

        if (! empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($this->admin_api . $endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);

        return is_array($decoded) ? $decoded : [];
    }
}
