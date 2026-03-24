<?php
/**
 * Tests for settings functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for settings functions.
 */
class Settings_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_all_settings returns array.
	 */
	public function test_get_all_settings_returns_array(): void {

		$result = wu_get_all_settings();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_setting returns default for nonexistent.
	 */
	public function test_get_setting_default(): void {

		$result = wu_get_setting('nonexistent_setting_xyz', 'default_value');

		$this->assertEquals('default_value', $result);
	}

	/**
	 * Test wu_save_setting and wu_get_setting roundtrip.
	 */
	public function test_save_and_get_setting(): void {

		wu_save_setting('test_setting_roundtrip', 'test_value');

		$result = wu_get_setting('test_setting_roundtrip');

		$this->assertEquals('test_value', $result);
	}

	/**
	 * Test wu_save_setting with boolean.
	 */
	public function test_save_setting_boolean(): void {

		wu_save_setting('test_bool_setting', true);

		$result = wu_get_setting('test_bool_setting');

		$this->assertTrue($result);
	}

	/**
	 * Test wu_save_setting with integer.
	 */
	public function test_save_setting_integer(): void {

		wu_save_setting('test_int_setting', 42);

		$result = wu_get_setting('test_int_setting');

		$this->assertEquals(42, $result);
	}

	/**
	 * Test wu_save_setting with array.
	 */
	public function test_save_setting_array(): void {

		wu_save_setting('test_array_setting', ['a', 'b', 'c']);

		$result = wu_get_setting('test_array_setting');

		$this->assertIsArray($result);
		$this->assertCount(3, $result);
	}

	/**
	 * Test wu_get_setting with false default.
	 */
	public function test_get_setting_false_default(): void {

		$result = wu_get_setting('nonexistent_xyz_123');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_network_favicon returns string.
	 */
	public function test_get_network_favicon_returns_string(): void {

		$result = wu_get_network_favicon();

		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_network_favicon with custom size.
	 */
	public function test_get_network_favicon_custom_size(): void {

		$result = wu_get_network_favicon('96');

		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_network_logo returns string.
	 */
	public function test_get_network_logo_returns_string(): void {

		$result = wu_get_network_logo();

		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_network_logo_attachement_id returns value.
	 */
	public function test_get_network_logo_attachment_id(): void {

		$result = wu_get_network_logo_attachement_id();

		$this->assertNotNull($result);
	}
}
