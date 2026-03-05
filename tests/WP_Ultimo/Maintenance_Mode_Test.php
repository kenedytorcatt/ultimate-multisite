<?php
/**
 * Unit tests for Maintenance_Mode class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Tests;

use WP_Ultimo\Maintenance_Mode;

class Maintenance_Mode_Test extends \WP_UnitTestCase {

	/**
	 * Test singleton instance is returned correctly.
	 */
	public function test_singleton_returns_instance(): void {

		$instance = Maintenance_Mode::get_instance();

		$this->assertInstanceOf(Maintenance_Mode::class, $instance);
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$instance = Maintenance_Mode::get_instance();
		$instance->init();

		$this->assertNotFalse(has_action('init', [$instance, 'add_settings']));
	}

	/**
	 * Test hooks are added when maintenance mode is enabled.
	 */
	public function test_hooks_added_when_maintenance_enabled(): void {

		wu_save_setting('maintenance_mode', true);

		$instance = Maintenance_Mode::get_instance();
		$instance->init();
		$instance->hooks();

		$this->assertNotFalse(has_action('wu_ajax_toggle_maintenance_mode', [$instance, 'toggle_maintenance_mode']));
	}

	/**
	 * Test check_maintenance_mode static method.
	 */
	public function test_check_maintenance_mode(): void {

		// Should not throw errors
		$result = Maintenance_Mode::check_maintenance_mode();

		$this->assertIsBool($result);
	}
}