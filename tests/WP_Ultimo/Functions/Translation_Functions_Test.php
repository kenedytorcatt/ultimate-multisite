<?php
/**
 * Tests for translation functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for translation functions.
 */
class Translation_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_translatable_string returns string for string input.
	 */
	public function test_wu_get_translatable_string_returns_string(): void {

		$result = wu_get_translatable_string('some_key');

		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_translatable_string returns non-string input unchanged.
	 */
	public function test_wu_get_translatable_string_non_string_input(): void {

		$result = wu_get_translatable_string(123);

		$this->assertSame(123, $result);
	}

	/**
	 * Test wu_get_translatable_string returns non-string for array input.
	 */
	public function test_wu_get_translatable_string_array_input(): void {

		$input  = ['key' => 'value'];
		$result = wu_get_translatable_string($input);

		$this->assertSame($input, $result);
	}

	/**
	 * Test wu_get_translatable_string filter is applied.
	 */
	public function test_wu_get_translatable_string_filter(): void {

		add_filter(
			'wu_translatable_strings',
			function ($strings) {
				$strings['custom_key'] = 'Custom Translation';
				return $strings;
			}
		);

		$result = wu_get_translatable_string('custom_key');

		$this->assertSame('Custom Translation', $result);

		remove_all_filters('wu_translatable_strings');
	}
}
