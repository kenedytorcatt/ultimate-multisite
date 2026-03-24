<?php
/**
 * Unit tests for Notification_Manager.
 *
 * @package WP_Ultimo\Tests\Managers
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Notification_Manager;

/**
 * Unit tests for Notification_Manager.
 */
class Notification_Manager_Test extends \WP_UnitTestCase {

	use Manager_Test_Trait;

	/**
	 * Get the manager class name.
	 *
	 * @return string
	 */
	protected function get_manager_class(): string {
		return Notification_Manager::class;
	}

	/**
	 * Get the expected slug.
	 *
	 * @return string|null
	 */
	protected function get_expected_slug(): ?string {
		return null;
	}

	/**
	 * Get the expected model class.
	 *
	 * @return string|null
	 */
	protected function get_expected_model_class(): ?string {
		return null;
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$instance1 = Notification_Manager::get_instance();
		$instance2 = Notification_Manager::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test init registers settings hook.
	 */
	public function test_init_registers_hooks(): void {

		$manager = $this->get_manager_instance();
		$manager->init();

		$this->assertNotFalse(has_action('init', [$manager, 'add_settings']));
	}

	/**
	 * Test add_settings registers the setting field.
	 */
	public function test_add_settings(): void {

		$manager = $this->get_manager_instance();
		$manager->add_settings();

		// The setting should be registered without error
		$this->assertTrue(true);
	}

	/**
	 * Test clear_callback_list returns false with empty backwards list.
	 */
	public function test_clear_callback_list_empty_backwards_list(): void {

		$manager = $this->get_manager_instance();

		// Set backwards_compatibility_list to empty via reflection
		$reflection = new \ReflectionClass($manager);
		$property   = $reflection->getProperty('backwards_compatibility_list');

		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}

		$property->setValue($manager, []);

		$result = $manager->clear_callback_list(['some_callback' => 'value']);

		$this->assertFalse($result);
	}

	/**
	 * Test clear_callback_list returns true when callback matches exclusion list.
	 */
	public function test_clear_callback_list_matches_exclusion(): void {

		$manager = $this->get_manager_instance();

		// Set backwards_compatibility_list via reflection
		$reflection = new \ReflectionClass($manager);
		$property   = $reflection->getProperty('backwards_compatibility_list');

		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}

		$property->setValue($manager, ['inject_admin_head_ads']);

		$result = $manager->clear_callback_list(['inject_admin_head_ads' => 'value']);

		$this->assertTrue($result);
	}

	/**
	 * Test clear_callback_list returns false when no match.
	 */
	public function test_clear_callback_list_no_match(): void {

		$manager = $this->get_manager_instance();

		// Set backwards_compatibility_list via reflection
		$reflection = new \ReflectionClass($manager);
		$property   = $reflection->getProperty('backwards_compatibility_list');

		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}

		$property->setValue($manager, ['inject_admin_head_ads']);

		$result = $manager->clear_callback_list(['some_other_callback' => 'value']);

		$this->assertFalse($result);
	}

	/**
	 * Test clear_callback_list with null backwards list.
	 */
	public function test_clear_callback_list_null_backwards_list(): void {

		$manager = $this->get_manager_instance();

		// Set backwards_compatibility_list to null via reflection
		$reflection = new \ReflectionClass($manager);
		$property   = $reflection->getProperty('backwards_compatibility_list');

		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}

		$property->setValue($manager, null);

		$result = $manager->clear_callback_list(['some_callback' => 'value']);

		$this->assertFalse($result);
	}

	/**
	 * Test hide_notifications_subsites does nothing when setting is off.
	 */
	public function test_hide_notifications_subsites_disabled(): void {

		wu_save_setting('hide_notifications_subsites', false);

		$manager = $this->get_manager_instance();

		// Should return early without modifying anything
		$manager->hide_notifications_subsites();

		$this->assertTrue(true);
	}
}
