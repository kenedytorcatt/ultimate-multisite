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

	/**
	 * Test wu_get_applicable_tax_rates returns '*' rate as fallback when no country match.
	 *
	 * When a tax rate has country='*' (Apply to all countries) and no per-country
	 * rate matches the customer's country, the '*' rate should be returned as a fallback.
	 */
	public function test_get_applicable_tax_rates_returns_wildcard_as_fallback(): void {

		$tax_rates = [
			'default' => [
				'name'  => 'Default',
				'rates' => [
					[
						'id'       => 'rate-wildcard',
						'title'    => 'Universal Rate',
						'country'  => '*',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 20,
					],
				],
			],
		];

		wu_save_option('tax_rates', $tax_rates);

		// Customer is in Germany — no per-country rate exists, so '*' should apply.
		$result = wu_get_applicable_tax_rates('DE');

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
		$this->assertEquals('Universal Rate', $result[0]['title']);
		$this->assertEquals('*', $result[0]['country']);
	}

	/**
	 * Test per-country rates take precedence over '*' (Apply to all countries) rate.
	 *
	 * When both a per-country rate and a '*' rate exist, the per-country rate
	 * should be returned and the '*' rate should NOT be included.
	 */
	public function test_get_applicable_tax_rates_country_specific_takes_precedence_over_wildcard(): void {

		$tax_rates = [
			'default' => [
				'name'  => 'Default',
				'rates' => [
					[
						'id'       => 'rate-us',
						'title'    => 'US Rate',
						'country'  => 'US',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 10,
					],
					[
						'id'       => 'rate-wildcard',
						'title'    => 'Universal Rate',
						'country'  => '*',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 20,
					],
				],
			],
		];

		wu_save_option('tax_rates', $tax_rates);

		// Customer is in the US — the per-country US rate should apply, not the wildcard.
		$result = wu_get_applicable_tax_rates('US');

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
		$this->assertEquals('US Rate', $result[0]['title']);
		$this->assertEquals('US', $result[0]['country']);

		// Wildcard should NOT be in the results when a country-specific rate matched.
		$countries = array_column($result, 'country');
		$this->assertNotContains('*', $countries);
	}

	/**
	 * Test '*' rate is not returned when a per-country rate matches.
	 *
	 * Verifies the fallback-only nature: '*' only applies when no country-specific
	 * rate exists for the customer's country.
	 */
	public function test_get_applicable_tax_rates_wildcard_not_returned_when_country_matches(): void {

		$tax_rates = [
			'default' => [
				'name'  => 'Default',
				'rates' => [
					[
						'id'       => 'rate-de',
						'title'    => 'Germany Rate',
						'country'  => 'DE',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 19,
					],
					[
						'id'       => 'rate-wildcard',
						'title'    => 'Universal Rate',
						'country'  => '*',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 15,
					],
				],
			],
		];

		wu_save_option('tax_rates', $tax_rates);

		$result = wu_get_applicable_tax_rates('DE');

		$this->assertCount(1, $result);
		$this->assertEquals('Germany Rate', $result[0]['title']);
	}

	/**
	 * Test '*' rate applies to any country with no specific rate configured.
	 */
	public function test_get_applicable_tax_rates_wildcard_applies_to_any_unmatched_country(): void {

		$tax_rates = [
			'default' => [
				'name'  => 'Default',
				'rates' => [
					[
						'id'       => 'rate-wildcard',
						'title'    => 'Universal Rate',
						'country'  => '*',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 10,
					],
				],
			],
		];

		wu_save_option('tax_rates', $tax_rates);

		foreach (['BR', 'JP', 'AU', 'CA'] as $country) {
			$result = wu_get_applicable_tax_rates($country);

			$this->assertNotEmpty($result, "Expected wildcard rate to apply for country: $country");
			$this->assertEquals('*', $result[0]['country'], "Expected '*' country for: $country");
		}
	}

	/**
	 * Test no rates returned when no country match and no '*' rate configured.
	 */
	public function test_get_applicable_tax_rates_empty_when_no_match_and_no_wildcard(): void {

		$tax_rates = [
			'default' => [
				'name'  => 'Default',
				'rates' => [
					[
						'id'       => 'rate-us',
						'title'    => 'US Rate',
						'country'  => 'US',
						'state'    => '',
						'city'     => '',
						'tax_rate' => 10,
					],
				],
			],
		];

		wu_save_option('tax_rates', $tax_rates);

		// Customer is in Germany — no US rate applies, no wildcard configured.
		$result = wu_get_applicable_tax_rates('DE');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}
}
