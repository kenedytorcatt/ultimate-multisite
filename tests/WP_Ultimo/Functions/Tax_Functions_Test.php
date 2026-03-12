<?php
/**
 * Tests for tax functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for tax functions.
 */
class Tax_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_should_collect_taxes returns bool.
	 */
	public function test_should_collect_taxes_returns_bool(): void {

		$result = wu_should_collect_taxes();

		$this->assertIsBool($result);
	}

	/**
	 * Test wu_should_collect_taxes with setting enabled.
	 */
	public function test_should_collect_taxes_enabled(): void {

		wu_save_setting('enable_taxes', true);

		$this->assertTrue(wu_should_collect_taxes());
	}

	/**
	 * Test wu_should_collect_taxes with setting disabled.
	 */
	public function test_should_collect_taxes_disabled(): void {

		wu_save_setting('enable_taxes', false);

		$this->assertFalse(wu_should_collect_taxes());
	}

	/**
	 * Test wu_get_tax_categories returns array.
	 */
	public function test_get_tax_categories_returns_array(): void {

		$result = wu_get_tax_categories();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_tax_category returns array with rates key.
	 */
	public function test_get_tax_category_returns_array_with_rates(): void {

		$result = wu_get_tax_category('nonexistent');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('rates', $result);
	}

	/**
	 * Test wu_get_tax_category default returns array.
	 */
	public function test_get_tax_category_default(): void {

		$result = wu_get_tax_category();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_tax_categories_as_options returns array.
	 */
	public function test_get_tax_categories_as_options_returns_array(): void {

		$result = wu_get_tax_categories_as_options();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_tax_amount with percentage exclusive.
	 */
	public function test_get_tax_amount_percentage_exclusive(): void {

		$result = wu_get_tax_amount(100, 10, 'percentage', false);

		$this->assertEquals(10.0, $result);
	}

	/**
	 * Test wu_get_tax_amount with percentage inclusive.
	 */
	public function test_get_tax_amount_percentage_inclusive(): void {

		$result = wu_get_tax_amount(110, 10, 'percentage', false, true);

		$this->assertEquals(10.0, $result);
	}

	/**
	 * Test wu_get_tax_amount with absolute type.
	 */
	public function test_get_tax_amount_absolute(): void {

		$result = wu_get_tax_amount(100, 5, 'absolute', false);

		$this->assertEquals(5.0, $result);
	}

	/**
	 * Test wu_get_tax_amount with format true.
	 */
	public function test_get_tax_amount_formatted(): void {

		$result = wu_get_tax_amount(100, 10, 'percentage', true);

		$this->assertIsString($result);
		$this->assertEquals('10.00', $result);
	}

	/**
	 * Test wu_get_tax_amount with zero base price.
	 */
	public function test_get_tax_amount_zero_base(): void {

		$result = wu_get_tax_amount(0, 10, 'percentage', false);

		$this->assertEquals(0.0, $result);
	}

	/**
	 * Test wu_get_tax_amount with zero rate.
	 */
	public function test_get_tax_amount_zero_rate(): void {

		$result = wu_get_tax_amount(100, 0, 'percentage', false);

		$this->assertEquals(0.0, $result);
	}

	/**
	 * Test wu_get_applicable_tax_rates with no country.
	 */
	public function test_get_applicable_tax_rates_no_country(): void {

		$result = wu_get_applicable_tax_rates('');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	/**
	 * Test wu_get_applicable_tax_rates with false country.
	 */
	public function test_get_applicable_tax_rates_false_country(): void {

		$result = wu_get_applicable_tax_rates(false);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	/**
	 * Test wu_get_applicable_tax_rates with nonexistent country.
	 */
	public function test_get_applicable_tax_rates_nonexistent_country(): void {

		$result = wu_get_applicable_tax_rates('XX');

		$this->assertIsArray($result);
	}
}
