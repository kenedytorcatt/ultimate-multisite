<?php
/**
 * Tests for user helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for user helper functions.
 */
class User_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_roles_as_options returns array.
	 */
	public function test_get_roles_as_options(): void {

		$result = wu_get_roles_as_options();

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
		$this->assertArrayHasKey('administrator', $result);
	}

	/**
	 * Test wu_get_roles_as_options with default option.
	 */
	public function test_get_roles_as_options_with_default(): void {

		$result = wu_get_roles_as_options(true);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('default', $result);
		$this->assertArrayHasKey('administrator', $result);
	}

	/**
	 * Test wu_get_roles_as_options includes common roles.
	 */
	public function test_get_roles_as_options_common_roles(): void {

		$result = wu_get_roles_as_options();

		$this->assertArrayHasKey('editor', $result);
		$this->assertArrayHasKey('subscriber', $result);
	}
}
