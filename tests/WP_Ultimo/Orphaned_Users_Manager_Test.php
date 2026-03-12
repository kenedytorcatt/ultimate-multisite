<?php
/**
 * Unit tests for Orphaned_Users_Manager.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

class Orphaned_Users_Manager_Test extends \WP_UnitTestCase {

	/**
	 * Get the singleton instance.
	 *
	 * @return Orphaned_Users_Manager
	 */
	protected function get_instance(): Orphaned_Users_Manager {

		return Orphaned_Users_Manager::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Orphaned_Users_Manager::class, $this->get_instance());
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			Orphaned_Users_Manager::get_instance(),
			Orphaned_Users_Manager::get_instance()
		);
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$instance = $this->get_instance();

		$instance->init();

		$this->assertIsInt(has_action('plugins_loaded', [$instance, 'register_forms']));
		$this->assertIsInt(has_action('wu_settings_other', [$instance, 'register_settings_field']));
	}

	/**
	 * Test find_orphaned_users returns array.
	 */
	public function test_find_orphaned_users_returns_array(): void {

		$result = $this->get_instance()->find_orphaned_users();

		$this->assertIsArray($result);
	}

	/**
	 * Test find_orphaned_users excludes super admins.
	 */
	public function test_find_orphaned_users_excludes_super_admins(): void {

		// Create a user and make them super admin
		$user_id = self::factory()->user->create();
		grant_super_admin($user_id);

		$orphaned = $this->get_instance()->find_orphaned_users();

		$orphaned_ids = array_map(function ($u) {
			return $u->ID;
		}, $orphaned);

		$this->assertNotContains($user_id, $orphaned_ids);

		// Cleanup
		revoke_super_admin($user_id);
	}

	/**
	 * Test delete_orphaned_users returns int.
	 */
	public function test_delete_orphaned_users_returns_int(): void {

		$result = $this->get_instance()->delete_orphaned_users([]);

		$this->assertSame(0, $result);
	}

	/**
	 * Test delete_orphaned_users skips super admins.
	 */
	public function test_delete_orphaned_users_skips_super_admins(): void {

		$user_id = self::factory()->user->create();
		grant_super_admin($user_id);

		$user = get_userdata($user_id);

		$result = $this->get_instance()->delete_orphaned_users([$user]);

		$this->assertSame(0, $result);

		// User should still exist
		$this->assertNotFalse(get_userdata($user_id));

		// Cleanup
		revoke_super_admin($user_id);
	}

	/**
	 * Test delete_orphaned_users deletes non-super-admin users.
	 */
	public function test_delete_orphaned_users_deletes_regular_users(): void {

		$user_id = self::factory()->user->create();

		$user = get_userdata($user_id);

		$result = $this->get_instance()->delete_orphaned_users([$user]);

		$this->assertSame(1, $result);

		// User should be deleted
		$this->assertFalse(get_userdata($user_id));
	}

	/**
	 * Test register_forms registers the orphaned_users_delete form.
	 */
	public function test_register_forms(): void {

		$instance = $this->get_instance();

		$instance->register_forms();

		$form_manager = \WP_Ultimo\Managers\Form_Manager::get_instance();

		$this->assertTrue($form_manager->is_form_registered('orphaned_users_delete'));
	}
}
