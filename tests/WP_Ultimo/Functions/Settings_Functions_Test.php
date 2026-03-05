<?php
/**
 * Unit tests for settings functions.
 *
 * @package WP_Ultimo\Tests\Functions
 */

namespace WP_Ultimo\Tests\Functions;

class Settings_Functions_Test extends \WP_UnitTestCase {

	/**
	 * Test wu_get_setting returns value.
	 */
	public function test_wu_get_setting_returns_value(): void {

		wu_save_setting('test_function_setting', 'function_value');

		$value = wu_get_setting('test_function_setting');

		$this->assertEquals('function_value', $value);
	}

	/**
	 * Test wu_get_setting returns default when not set.
	 */
	public function test_wu_get_setting_returns_default(): void {

		$value = wu_get_setting('nonexistent_setting', 'default');

		$this->assertEquals('default', $value);
	}

	/**
	 * Test wu_save_setting stores value.
	 */
	public function test_wu_save_setting_stores_value(): void {

		$result = wu_save_setting('test_save_setting', 'save_value');

		$this->assertTrue($result);

		$retrieved = wu_get_setting('test_save_setting');
		$this->assertEquals('save_value', $retrieved);
	}

	/**
	 * Test wu_save_setting handles boolean values.
	 */
	public function test_wu_save_setting_handles_boolean(): void {

		wu_save_setting('bool_setting', true);

		$value = wu_get_setting('bool_setting');

		$this->assertTrue($value);
	}

	/**
	 * Test wu_save_setting handles integer values.
	 */
	public function test_wu_save_setting_handles_integer(): void {

		wu_save_setting('int_setting', 42);

		$value = wu_get_setting('int_setting');

		$this->assertEquals(42, $value);
	}

	/**
	 * Test wu_save_setting handles array values.
	 */
	public function test_wu_save_setting_handles_array(): void {

		$array = ['key1' => 'value1', 'key2' => 'value2'];

		wu_save_setting('array_setting', $array);

		$value = wu_get_setting('array_setting');

		$this->assertEquals($array, $value);
	}

	/**
	 * Test boundary: Empty string setting value.
	 */
	public function test_wu_save_setting_handles_empty_string(): void {

		wu_save_setting('empty_string_setting', '');

		$value = wu_get_setting('empty_string_setting');

		$this->assertEquals('', $value);
	}

	/**
	 * Test boundary: Zero value.
	 */
	public function test_wu_save_setting_handles_zero(): void {

		wu_save_setting('zero_setting', 0);

		$value = wu_get_setting('zero_setting');

		$this->assertEquals(0, $value);
	}

	/**
	 * Test boundary: Null value.
	 */
	public function test_wu_save_setting_handles_null(): void {

		wu_save_setting('null_setting', null);

		$value = wu_get_setting('null_setting');

		$this->assertNull($value);
	}

	/**
	 * Test setting with special characters in key.
	 */
	public function test_wu_get_setting_with_special_chars(): void {

		wu_save_setting('test_setting_with_underscores', 'value');

		$value = wu_get_setting('test_setting_with_underscores');

		$this->assertEquals('value', $value);
	}
}