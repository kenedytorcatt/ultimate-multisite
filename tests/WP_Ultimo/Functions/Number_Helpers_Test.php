<?php
/**
 * Tests for number helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for number helper functions.
 */
class Number_Helpers_Test extends WP_UnitTestCase {

	/**
	 * Test wu_extract_number with simple number.
	 */
	public function test_extract_number_simple(): void {
		$this->assertEquals(123, wu_extract_number('123'));
		$this->assertEquals(456, wu_extract_number('456'));
	}

	/**
	 * Test wu_extract_number with text and number.
	 */
	public function test_extract_number_with_text(): void {
		$this->assertEquals(42, wu_extract_number('The answer is 42'));
		$this->assertEquals(100, wu_extract_number('100 items'));
		$this->assertEquals(5, wu_extract_number('5GB'));
	}

	/**
	 * Test wu_extract_number with number in middle of text.
	 */
	public function test_extract_number_in_middle(): void {
		$this->assertEquals(25, wu_extract_number('There are 25 apples in the box'));
	}

	/**
	 * Test wu_extract_number with multiple numbers.
	 */
	public function test_extract_number_multiple_numbers(): void {
		// Should return the first number found
		$this->assertEquals(10, wu_extract_number('10 cats and 20 dogs'));
	}

	/**
	 * Test wu_extract_number with no number.
	 */
	public function test_extract_number_no_number(): void {
		$this->assertEquals(0, wu_extract_number('no numbers here'));
		$this->assertEquals(0, wu_extract_number(''));
	}

	/**
	 * Test wu_extract_number with special characters.
	 */
	public function test_extract_number_special_chars(): void {
		$this->assertEquals(99, wu_extract_number('$99.99'));
		$this->assertEquals(100, wu_extract_number('100%'));
	}

	/**
	 * Test wu_to_float with integer.
	 */
	public function test_to_float_with_integer(): void {
		$this->assertEqualsWithDelta(100.0, wu_to_float(100), 0.001);
		$this->assertEqualsWithDelta(0.0, wu_to_float(0), 0.001);
	}

	/**
	 * Test wu_to_float with float.
	 */
	public function test_to_float_with_float(): void {
		$this->assertEqualsWithDelta(99.99, wu_to_float(99.99), 0.001);
		$this->assertEqualsWithDelta(0.5, wu_to_float(0.5), 0.001);
	}

	/**
	 * Test wu_to_float with string number.
	 */
	public function test_to_float_with_string(): void {
		$this->assertEqualsWithDelta(100.0, wu_to_float('100'), 0.001);
		$this->assertEqualsWithDelta(99.99, wu_to_float('99.99', '.'), 0.001);
	}

	/**
	 * Test wu_to_float with formatted string.
	 */
	public function test_to_float_formatted_string(): void {
		$this->assertEqualsWithDelta(1000.0, wu_to_float('1,000', '.'), 0.001);
		$this->assertEqualsWithDelta(1000.5, wu_to_float('1,000.50', '.'), 0.001);
	}

	/**
	 * Test wu_to_float with currency symbol.
	 */
	public function test_to_float_with_currency(): void {
		$this->assertEqualsWithDelta(99.99, wu_to_float('$99.99', '.'), 0.001);
		$this->assertEqualsWithDelta(100.0, wu_to_float('€100', '.'), 0.001);
	}

	/**
	 * Test wu_to_float with European format.
	 */
	public function test_to_float_european_format(): void {
		// Note: wu_to_float keeps the decimal separator but doesn't convert it to period
		// PHP's floatval() doesn't recognize comma as decimal separator
		// So '99,99' with comma decimal separator stays '99,99' and floatval returns 99.0
		$result = wu_to_float('99,99', ',');
		$this->assertIsFloat($result);
		// The function preserves the comma but floatval truncates at it
		$this->assertEqualsWithDelta(99.0, $result, 0.001);
	}

	/**
	 * Test wu_to_float with negative numbers.
	 */
	public function test_to_float_negative(): void {
		$this->assertEqualsWithDelta(-100.0, wu_to_float(-100), 0.001);
		$this->assertEqualsWithDelta(-99.99, wu_to_float('-99.99', '.'), 0.001);
	}

	/**
	 * Test wu_to_float returns float type.
	 */
	public function test_to_float_returns_float_type(): void {
		$this->assertIsFloat(wu_to_float(100));
		$this->assertIsFloat(wu_to_float('100'));
		$this->assertIsFloat(wu_to_float('99.99', '.'));
	}
}
