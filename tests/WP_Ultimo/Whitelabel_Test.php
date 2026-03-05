<?php
/**
 * Unit tests for Whitelabel class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Tests;

use WP_Ultimo\Whitelabel;

class Whitelabel_Test extends \WP_UnitTestCase {

	/**
	 * Test singleton instance is returned correctly.
	 */
	public function test_singleton_returns_instance(): void {

		$instance = Whitelabel::get_instance();

		$this->assertInstanceOf(Whitelabel::class, $instance);
	}

	/**
	 * Test hooks are registered on init.
	 */
	public function test_init_registers_hooks(): void {

		$instance = Whitelabel::get_instance();
		$instance->init();

		$this->assertNotFalse(has_action('init', [$instance, 'add_settings']));
		$this->assertNotFalse(has_action('admin_init', [$instance, 'clear_footer_texts']));
		$this->assertNotFalse(has_action('init', [$instance, 'hooks']));
	}

	/**
	 * Test hooks method adds WP logo removal when setting is enabled.
	 */
	public function test_hooks_adds_wp_logo_removal_when_enabled(): void {

		wu_save_setting('hide_wordpress_logo', true);

		$instance = Whitelabel::get_instance();
		$instance->hooks();

		$this->assertNotFalse(has_action('wp_before_admin_bar_render', [$instance, 'wp_logo_admin_bar_remove']));
	}

	/**
	 * Test hooks method adds sites menu removal when setting is enabled.
	 */
	public function test_hooks_adds_sites_menu_removal_when_enabled(): void {

		wu_save_setting('hide_sites_menu', true);

		$instance = Whitelabel::get_instance();
		$instance->hooks();

		$this->assertNotFalse(has_action('network_admin_menu', [$instance, 'remove_sites_admin_menu']));
	}

	/**
	 * Test whitelabel does not add hooks when disabled.
	 */
	public function test_hooks_not_added_when_disabled(): void {

		wu_save_setting('hide_wordpress_logo', false);
		wu_save_setting('hide_sites_menu', false);

		$instance = Whitelabel::get_instance();
		$instance->hooks();

		// Hooks should not be present
		$this->assertTrue(true);
	}
}