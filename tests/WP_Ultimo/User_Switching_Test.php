<?php
/**
 * Unit tests for User_Switching.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

class User_Switching_Test extends \WP_UnitTestCase {

	/**
	 * Get the singleton instance.
	 *
	 * @return User_Switching
	 */
	protected function get_instance(): User_Switching {

		return User_Switching::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(User_Switching::class, $this->get_instance());
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			User_Switching::get_instance(),
			User_Switching::get_instance()
		);
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$instance = $this->get_instance();

		$instance->init();

		$this->assertIsInt(has_action('plugins_loaded', [$instance, 'register_forms']));
	}

	/**
	 * Test check_user_switching_is_activated returns bool.
	 */
	public function test_check_user_switching_is_activated_returns_bool(): void {

		$instance = $this->get_instance();

		$result = $instance->check_user_switching_is_activated();

		$this->assertIsBool($result);
	}

	/**
	 * Test check_user_switching_is_activated returns false when plugin not active.
	 */
	public function test_check_user_switching_not_activated(): void {

		$instance = $this->get_instance();

		// The user_switching class is not loaded in test env
		$this->assertFalse($instance->check_user_switching_is_activated());
	}

	/**
	 * Test register_forms registers the install_user_switching form.
	 */
	public function test_register_forms(): void {

		$instance = $this->get_instance();

		$instance->register_forms();

		$form_manager = \WP_Ultimo\Managers\Form_Manager::get_instance();

		$this->assertTrue($form_manager->is_form_registered('install_user_switching'));
	}

	/**
	 * Test render returns a URL when user_switching is not active.
	 */
	public function test_render_returns_form_url_when_plugin_not_active(): void {

		$instance = $this->get_instance();

		// Register the form first
		$instance->register_forms();

		$user_id = self::factory()->user->create();

		$result = $instance->render($user_id);

		$this->assertIsString($result);
		$this->assertStringContainsString('install_user_switching', $result);
	}
}
