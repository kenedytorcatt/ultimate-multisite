<?php
/**
 * Multisite Network Installer.
 *
 * Handles the step-by-step installation of a WordPress Multisite network
 * via AJAX, used by the Multisite Setup wizard page.
 *
 * @package WP_Ultimo
 * @subpackage Installers
 * @since 2.0.0
 */

namespace WP_Ultimo\Installers;

use Psr\Log\LogLevel;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Multisite Network Installer.
 *
 * @since 2.0.0
 */
class Multisite_Network_Installer extends Base_Installer {
	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Transient key for storing the network configuration.
	 *
	 * @var string
	 */
	const CONFIG_TRANSIENT = 'wu_multisite_network_config';

	/**
	 * Returns the list of installation steps.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_steps() {

		global $wpdb;

		$has_multisite_constant = defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE;
		$has_network_tables     = $this->check_network_tables_exist();
		$has_wp_config_updated  = defined('MULTISITE') && MULTISITE; // @phpstan-ignore phpstanWP.wpConstant.fetch

		return [
			'enable_multisite' => [
				'done'        => $has_multisite_constant,
				'title'       => __('Enable Multisite', 'ultimate-multisite'),
				'description' => __('Adds WP_ALLOW_MULTISITE constant to wp-config.php.', 'ultimate-multisite'),
				'pending'     => __('Pending', 'ultimate-multisite'),
				'installing'  => __('Enabling multisite...', 'ultimate-multisite'),
				'success'     => __('Success!', 'ultimate-multisite'),
				'help'        => '',
			],
			'create_network'   => [
				'done'        => $has_network_tables,
				'title'       => __('Create Network', 'ultimate-multisite'),
				'description' => __('Creates network database tables and populates network data.', 'ultimate-multisite'),
				'pending'     => __('Pending', 'ultimate-multisite'),
				'installing'  => __('Creating network tables...', 'ultimate-multisite'),
				'success'     => __('Success!', 'ultimate-multisite'),
				'help'        => '',
			],
			'update_wp_config' => [
				'done'        => $has_wp_config_updated,
				'title'       => __('Update Configuration', 'ultimate-multisite'),
				'description' => __('Adds final multisite constants to wp-config.php.', 'ultimate-multisite'),
				'pending'     => __('Pending', 'ultimate-multisite'),
				'installing'  => __('Updating configuration...', 'ultimate-multisite'),
				'success'     => __('Success!', 'ultimate-multisite'),
				'help'        => '',
			],
			'cookie_fix'       => [
				'done'        => $has_wp_config_updated,
				'title'       => __('Fix Cookies', 'ultimate-multisite'),
				'description' => __('Ensures site URL is correct to prevent cookie issues after activation.', 'ultimate-multisite'),
				'pending'     => __('Pending', 'ultimate-multisite'),
				'installing'  => __('Fixing cookies...', 'ultimate-multisite'),
				'success'     => __('Success!', 'ultimate-multisite'),
				'help'        => '',
			],
			'network_activate' => [
				'done'        => $this->is_network_activated(),
				'title'       => __('Network Activate Plugin', 'ultimate-multisite'),
				'description' => __('Network-activates Ultimate Multisite so it runs across the entire network.', 'ultimate-multisite'),
				'pending'     => __('Pending', 'ultimate-multisite'),
				'installing'  => __('Activating plugin...', 'ultimate-multisite'),
				'success'     => __('Success!', 'ultimate-multisite'),
				'help'        => '',
			],
		];
	}

	/**
	 * Checks whether Ultimate Multisite is network-activated.
	 *
	 * Uses direct DB query because this may run before multisite
	 * is active in the current PHP process.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	protected function is_network_activated(): bool {

		global $wpdb;

		$sitemeta_table = $wpdb->base_prefix . 'sitemeta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare('SHOW TABLES LIKE %s', $sitemeta_table)
		);

		if ($table_exists !== $sitemeta_table) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_plugins = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $sitemeta_table WHERE site_id = 1 AND meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'active_sitewide_plugins'
			)
		);

		if (empty($active_plugins)) {
			return false;
		}

		$active_plugins = maybe_unserialize($active_plugins);

		return is_array($active_plugins) && isset($active_plugins['ultimate-multisite/ultimate-multisite.php']);
	}

	/**
	 * Checks whether the multisite network tables exist.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	protected function check_network_tables_exist(): bool {

		global $wpdb;

		$table_name = $wpdb->base_prefix . 'site';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare('SHOW TABLES LIKE %s', $table_name)
		);

		return $result === $table_name;
	}

	/**
	 * Returns the stored network configuration from the transient.
	 *
	 * @since 2.0.0
	 * @throws \Exception When no configuration is found.
	 * @return array
	 */
	protected function get_config(): array {

		$config = get_transient(self::CONFIG_TRANSIENT);

		if (empty($config) || ! is_array($config)) {
			throw new \Exception(esc_html__('Network configuration not found. Please go back and submit the configuration form again.', 'ultimate-multisite'));
		}

		return $config;
	}

	/**
	 * Step 1: Enable multisite by adding WP_ALLOW_MULTISITE to wp-config.php.
	 *
	 * @since 2.0.0
	 * @throws \Exception When the constant cannot be injected.
	 * @return void
	 */
	public function _install_enable_multisite(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		if (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE) {
			return;
		}

		$wp_config = \WP_Ultimo\Helpers\WP_Config::get_instance();

		$result = $wp_config->inject_wp_config_constant('WP_ALLOW_MULTISITE', true);

		if (is_wp_error($result)) {
			throw new \Exception(esc_html($result->get_error_message()));
		}
	}

	/**
	 * Step 2: Create network tables and populate network data.
	 *
	 * @since 2.0.0
	 * @throws \Exception When network creation fails.
	 * @return void
	 */
	public function _install_create_network(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		global $wpdb;

		if ($this->check_network_tables_exist()) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$has_data = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->base_prefix}site");

			if ($has_data) {
				return;
			}
		}

		$config = $this->get_config();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if (! function_exists('install_network')) {
			require_once ABSPATH . 'wp-admin/includes/network.php';
		}

		// On a single-site install, $wpdb doesn't have multisite table names set.
		foreach ($wpdb->ms_global_tables as $table) {
			$wpdb->$table = $wpdb->base_prefix . $table;
		}

		install_network();

		$result = populate_network(
			1,
			$config['domain'],
			$config['email'],
			$config['sitename'],
			$config['base'],
			$config['subdomain_install']
		);

		// populate_network() returns WP_Error('no_wildcard_dns') for subdomain
		// installs when wildcard DNS isn't configured. This is a warning, not
		// a fatal error — the network tables are still created successfully.
		if (is_wp_error($result) && ! in_array($result->get_error_code(), ['no_wildcard_dns', 'siteid_exists'], true)) {
			throw new \Exception(esc_html($result->get_error_message()));
		}

		// Fix siteurl trailing slash to prevent cookie hash change.
		// Is this really needed?
		// $wpdb->update(
		// $wpdb->sitemeta,
		// ['meta_value' => untrailingslashit(get_option('siteurl'))], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		// [
		// 'site_id'  => 1,
		// 'meta_key' => 'siteurl', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		// ]
		// );
	}

	/**
	 * Step 3: Add final multisite constants to wp-config.php.
	 *
	 * @since 2.0.0
	 * @throws \Exception When constants cannot be injected.
	 * @return void
	 */
	public function _install_update_wp_config(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		if (defined('MULTISITE') && MULTISITE) { // @phpstan-ignore phpstanWP.wpConstant.fetch
			return;
		}

		$config = $this->get_config();

		$wp_config = \WP_Ultimo\Helpers\WP_Config::get_instance();

		$constants = [
			'MULTISITE'            => true,
			'SUBDOMAIN_INSTALL'    => $config['subdomain_install'],
			'DOMAIN_CURRENT_SITE'  => $config['domain'],
			'PATH_CURRENT_SITE'    => '/',
			'SITE_ID_CURRENT_SITE' => 1,
			'BLOG_ID_CURRENT_SITE' => 1,
		];

		foreach ($constants as $constant => $value) {
			$result = $wp_config->inject_wp_config_constant($constant, $value);

			if (is_wp_error($result)) {
				throw new \Exception(esc_html($result->get_error_message()));
			}
		}
	}

	/**
	 * Step 4: Verify and fix the siteurl trailing slash in sitemeta.
	 *
	 * This is an idempotent safety check to ensure the cookie hash
	 * remains consistent after multisite activation.
	 *
	 * @since 2.0.0
	 * @throws \Exception When the fix fails.
	 * @return void
	 */
	public function _install_cookie_fix(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		global $wpdb;

		$sitemeta_table = $wpdb->base_prefix . 'sitemeta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare('SHOW TABLES LIKE %s', $sitemeta_table)
		);

		if ($table_exists !== $sitemeta_table) {
			return;
		}

		$siteurl = get_option('siteurl');

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$sitemeta_table,
			['meta_value' => untrailingslashit($siteurl)], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			[
				'site_id'  => 1,
				'meta_key' => 'siteurl', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			]
		);
	}

	/**
	 * Step 5: Network-activate Ultimate Multisite.
	 *
	 * Writes directly to the sitemeta table because multisite
	 * is not yet active in the current PHP process.
	 *
	 * @since 2.0.0
	 * @throws \Exception When the activation fails.
	 * @return void
	 */
	public function _install_network_activate(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		if ($this->is_network_activated()) {
			return;
		}

		global $wpdb;

		$sitemeta_table = $wpdb->base_prefix . 'sitemeta';
		$plugin         = 'ultimate-multisite/ultimate-multisite.php';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare('SHOW TABLES LIKE %s', $sitemeta_table)
		);

		if ($table_exists !== $sitemeta_table) {
			throw new \Exception(esc_html__('The sitemeta table does not exist. Network tables must be created first.', 'ultimate-multisite'));
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_plugins = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $sitemeta_table WHERE site_id = 1 AND meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'active_sitewide_plugins'
			)
		);

		$active_plugins = ! empty($active_plugins) ? maybe_unserialize($active_plugins) : [];

		if (! is_array($active_plugins)) {
			$active_plugins = [];
		}

		$active_plugins[ $plugin ] = time();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $sitemeta_table WHERE site_id = 1 AND meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'active_sitewide_plugins'
			)
		);

		if ($existing) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$sitemeta_table,
				['meta_value' => maybe_serialize($active_plugins)], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				[
					'site_id'  => 1,
					'meta_key' => 'active_sitewide_plugins', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$sitemeta_table,
				[
					'site_id'    => 1,
					'meta_key'   => 'active_sitewide_plugins', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => maybe_serialize($active_plugins), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				]
			);
		}

		if (false === $result) {
			throw new \Exception(esc_html__('Failed to network-activate Ultimate Multisite.', 'ultimate-multisite'));
		}
	}
}
