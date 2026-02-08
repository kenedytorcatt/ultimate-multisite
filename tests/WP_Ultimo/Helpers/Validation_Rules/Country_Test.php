<?php
/**
 * Tests for Country validation rule.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Helpers\Validation_Rules;

use WP_UnitTestCase;

/**
 * Test class for Country validation rule.
 */
class Country_Test extends WP_UnitTestCase {

	/**
	 * @var Country
	 */
	private $rule;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->rule = new Country();
	}

	/**
	 * Test valid country code US.
	 */
	public function test_valid_country_us(): void {
		$this->assertTrue($this->rule->check('US'));
	}

	/**
	 * Test valid country code lowercase.
	 */
	public function test_valid_country_lowercase(): void {
		$this->assertTrue($this->rule->check('us'));
	}

	/**
	 * Test valid country code UK.
	 */
	public function test_valid_country_gb(): void {
		$this->assertTrue($this->rule->check('GB'));
	}

	/**
	 * Test valid country code Canada.
	 */
	public function test_valid_country_ca(): void {
		$this->assertTrue($this->rule->check('CA'));
	}

	/**
	 * Test valid country code Germany.
	 */
	public function test_valid_country_de(): void {
		$this->assertTrue($this->rule->check('DE'));
	}

	/**
	 * Test valid country code Brazil.
	 */
	public function test_valid_country_br(): void {
		$this->assertTrue($this->rule->check('BR'));
	}

	/**
	 * Test valid country code Australia.
	 */
	public function test_valid_country_au(): void {
		$this->assertTrue($this->rule->check('AU'));
	}

	/**
	 * Test invalid country code.
	 */
	public function test_invalid_country_code(): void {
		$this->assertFalse($this->rule->check('XX'));
	}

	/**
	 * Test invalid country three letter code.
	 */
	public function test_invalid_country_three_letters(): void {
		$this->assertFalse($this->rule->check('USA'));
	}

	/**
	 * Test invalid country numeric.
	 */
	public function test_invalid_country_numeric(): void {
		$this->assertFalse($this->rule->check('123'));
	}

	/**
	 * Test empty string is valid.
	 */
	public function test_empty_string_is_valid(): void {
		$this->assertTrue($this->rule->check(''));
	}

	/**
	 * Test null value is valid.
	 */
	public function test_null_is_valid(): void {
		$this->assertTrue($this->rule->check(null));
	}

	/**
	 * Test false value is valid.
	 */
	public function test_false_is_valid(): void {
		$this->assertTrue($this->rule->check(false));
	}

	/**
	 * Test mixed case country code.
	 */
	public function test_mixed_case_country(): void {
		$this->assertTrue($this->rule->check('Us'));
	}

	/**
	 * Test valid country code France.
	 */
	public function test_valid_country_fr(): void {
		$this->assertTrue($this->rule->check('FR'));
	}

	/**
	 * Test valid country code Japan.
	 */
	public function test_valid_country_jp(): void {
		$this->assertTrue($this->rule->check('JP'));
	}
}
