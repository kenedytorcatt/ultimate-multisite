<?php
/**
 * Tests for string helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for string helper functions.
 */
class String_Helpers_Test extends WP_UnitTestCase {

	/**
	 * Test wu_string_to_bool with various truthy values.
	 */
	public function test_string_to_bool_truthy_values(): void {
		$this->assertTrue(wu_string_to_bool('yes'));
		$this->assertTrue(wu_string_to_bool('YES'));
		$this->assertTrue(wu_string_to_bool('Yes'));
		$this->assertTrue(wu_string_to_bool('on'));
		$this->assertTrue(wu_string_to_bool('ON'));
		$this->assertTrue(wu_string_to_bool('true'));
		$this->assertTrue(wu_string_to_bool('TRUE'));
		$this->assertTrue(wu_string_to_bool('True'));
		$this->assertTrue(wu_string_to_bool('1'));
		$this->assertTrue(wu_string_to_bool(1));
		$this->assertTrue(wu_string_to_bool(true));
	}

	/**
	 * Test wu_string_to_bool with various falsy values.
	 */
	public function test_string_to_bool_falsy_values(): void {
		$this->assertFalse(wu_string_to_bool('no'));
		$this->assertFalse(wu_string_to_bool('NO'));
		$this->assertFalse(wu_string_to_bool('off'));
		$this->assertFalse(wu_string_to_bool('false'));
		$this->assertFalse(wu_string_to_bool('0'));
		$this->assertFalse(wu_string_to_bool(0));
		$this->assertFalse(wu_string_to_bool(''));
		$this->assertFalse(wu_string_to_bool(false));
	}

	/**
	 * Test wu_string_to_bool with edge cases.
	 */
	public function test_string_to_bool_edge_cases(): void {
		$this->assertFalse(wu_string_to_bool('random'));
		$this->assertFalse(wu_string_to_bool('yesno'));
		$this->assertFalse(wu_string_to_bool('2'));
	}

	/**
	 * Test wu_slug_to_name with underscores.
	 */
	public function test_slug_to_name_with_underscores(): void {
		$this->assertEquals('Discount Code', wu_slug_to_name('discount_code'));
		$this->assertEquals('My Test Slug', wu_slug_to_name('my_test_slug'));
		$this->assertEquals('Single', wu_slug_to_name('single'));
	}

	/**
	 * Test wu_slug_to_name with dashes.
	 */
	public function test_slug_to_name_with_dashes(): void {
		$this->assertEquals('Discount Code', wu_slug_to_name('discount-code'));
		$this->assertEquals('My Test Slug', wu_slug_to_name('my-test-slug'));
	}

	/**
	 * Test wu_slug_to_name with mixed separators.
	 */
	public function test_slug_to_name_mixed_separators(): void {
		$this->assertEquals('My Test Slug', wu_slug_to_name('my-test_slug'));
		$this->assertEquals('Foo Bar Baz', wu_slug_to_name('foo_bar-baz'));
	}

	/**
	 * Test wu_slug_to_name with empty string.
	 */
	public function test_slug_to_name_empty_string(): void {
		$this->assertEquals('', wu_slug_to_name(''));
	}

	/**
	 * Test wu_replace_dashes function.
	 */
	public function test_replace_dashes(): void {
		$this->assertEquals('foo_bar', wu_replace_dashes('foo-bar'));
		$this->assertEquals('foo_bar_baz', wu_replace_dashes('foo-bar-baz'));
		$this->assertEquals('no_dashes_here', wu_replace_dashes('no_dashes_here'));
		$this->assertEquals('', wu_replace_dashes(''));
	}

	/**
	 * Test wu_get_initials with two words.
	 */
	public function test_get_initials_two_words(): void {
		$this->assertEquals('BP', wu_get_initials('Brazilian People'));
		$this->assertEquals('JD', wu_get_initials('John Doe'));
		$this->assertEquals('AB', wu_get_initials('Alice Bob'));
	}

	/**
	 * Test wu_get_initials with single word.
	 */
	public function test_get_initials_single_word(): void {
		$this->assertEquals('J', wu_get_initials('John'));
		$this->assertEquals('A', wu_get_initials('Alice'));
	}

	/**
	 * Test wu_get_initials with more than two words.
	 */
	public function test_get_initials_multiple_words(): void {
		$this->assertEquals('JD', wu_get_initials('John David Smith'));
		$this->assertEquals('AB', wu_get_initials('Alice Bob Carol'));
	}

	/**
	 * Test wu_get_initials with custom max_size.
	 */
	public function test_get_initials_custom_max_size(): void {
		$this->assertEquals('JDS', wu_get_initials('John David Smith', 3));
		$this->assertEquals('ABCD', wu_get_initials('Alice Bob Carol David', 4));
		$this->assertEquals('J', wu_get_initials('John David Smith', 1));
	}

	/**
	 * Test wu_get_initials with empty string.
	 */
	public function test_get_initials_empty_string(): void {
		$this->assertEquals('', wu_get_initials(''));
	}

	/**
	 * Test wu_get_initials returns uppercase.
	 */
	public function test_get_initials_returns_uppercase(): void {
		$this->assertEquals('JD', wu_get_initials('john doe'));
		$this->assertEquals('AB', wu_get_initials('alice bob'));
	}
}
