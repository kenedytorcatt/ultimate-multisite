<?php
/**
 * Tests for compatibility functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for compatibility functions.
 */
class Compatibility_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test current_user_can_for_site function exists.
	 */
	public function test_current_user_can_for_site_exists(): void {

		$this->assertTrue(function_exists('current_user_can_for_site'));
	}

	/**
	 * Test current_user_can_for_site returns bool.
	 */
	public function test_current_user_can_for_site_returns_bool(): void {

		$admin_id = self::factory()->user->create(['role' => 'administrator']);

		wp_set_current_user($admin_id);

		$result = current_user_can_for_site(1, 'read');

		$this->assertIsBool($result);
	}

	/**
	 * Test current_user_can_for_site with non-admin user.
	 */
	public function test_current_user_can_for_site_non_admin(): void {

		$subscriber_id = self::factory()->user->create(['role' => 'subscriber']);

		wp_set_current_user($subscriber_id);

		$result = current_user_can_for_site(1, 'manage_options');

		$this->assertFalse($result);
	}
}
