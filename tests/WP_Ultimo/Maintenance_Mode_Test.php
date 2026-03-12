<?php
/**
 * Unit tests for Maintenance_Mode.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

class Maintenance_Mode_Test extends \WP_UnitTestCase {

	/**
	 * Get the singleton instance.
	 *
	 * @return Maintenance_Mode
	 */
	protected function get_instance(): Maintenance_Mode {

		return Maintenance_Mode::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Maintenance_Mode::class, $this->get_instance());
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			Maintenance_Mode::get_instance(),
			Maintenance_Mode::get_instance()
		);
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$instance = $this->get_instance();

		$instance->init();

		$this->assertIsInt(has_action('init', [$instance, 'add_settings']));
	}

	/**
	 * Test hooks method registers ajax action.
	 */
	public function test_hooks_registers_ajax_action(): void {

		$instance = $this->get_instance();

		$instance->hooks();

		$this->assertIsInt(has_action('wu_ajax_toggle_maintenance_mode', [$instance, 'toggle_maintenance_mode']));
	}

	/**
	 * Test check_maintenance_mode returns falsy by default.
	 */
	public function test_check_maintenance_mode_returns_false_by_default(): void {

		$result = Maintenance_Mode::check_maintenance_mode();

		$this->assertEmpty($result);
	}

	/**
	 * Test check_maintenance_mode returns true when set.
	 */
	public function test_check_maintenance_mode_returns_true_when_set(): void {

		$blog_id = get_current_blog_id();

		update_site_meta($blog_id, 'wu_maintenance_mode', true);

		$result = Maintenance_Mode::check_maintenance_mode();

		$this->assertTrue((bool) $result);

		// Cleanup
		delete_site_meta($blog_id, 'wu_maintenance_mode');
	}

	/**
	 * Test add_settings is callable.
	 */
	public function test_add_settings_is_callable(): void {

		$instance = $this->get_instance();

		// Should not throw
		$instance->add_settings();

		$this->assertTrue(true);
	}
}
