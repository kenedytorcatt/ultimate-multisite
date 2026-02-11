<?php
/**
 * Tests for State validation rule.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Helpers\Validation_Rules;

use WP_UnitTestCase;

/**
 * Test class for State validation rule.
 */
class State_Test extends WP_UnitTestCase {

	/**
	 * @var State
	 */
	private $rule;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->rule = new State();
	}

	/**
	 * Test valid US state.
	 */
	public function test_valid_us_state(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check('CA'));
	}

	/**
	 * Test valid US state lowercase.
	 */
	public function test_valid_us_state_lowercase(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check('ca'));
	}

	/**
	 * Test valid US state New York.
	 */
	public function test_valid_us_state_ny(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check('NY'));
	}

	/**
	 * Test valid US state Texas.
	 */
	public function test_valid_us_state_tx(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check('TX'));
	}

	/**
	 * Test empty state without country is valid.
	 */
	public function test_empty_state_no_country(): void {
		$this->assertTrue($this->rule->check(''));
	}

	/**
	 * Test empty state with country is valid.
	 */
	public function test_empty_state_with_country(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check(''));
	}

	/**
	 * Test null state is valid.
	 */
	public function test_null_state(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check(null));
	}

	/**
	 * Test state without country param is valid.
	 */
	public function test_state_without_country_param(): void {
		$this->assertTrue($this->rule->check('CA'));
	}

	/**
	 * Test fillable params.
	 */
	public function test_fillable_params(): void {
		$reflection = new \ReflectionClass($this->rule);
		$property   = $reflection->getProperty('fillableParams');
		$property->setAccessible(true);

		$params = $property->getValue($this->rule);
		$this->assertContains('country', $params);
	}

	/**
	 * Test mixed case state.
	 */
	public function test_mixed_case_state(): void {
		$this->rule->setParameters(['country' => 'US']);
		$this->assertTrue($this->rule->check('Ny'));
	}

	/**
	 * Test valid Canadian province.
	 */
	public function test_valid_canadian_province(): void {
		$this->rule->setParameters(['country' => 'CA']);
		$this->assertTrue($this->rule->check('ON'));
	}

	/**
	 * Test valid Canadian province BC.
	 */
	public function test_valid_canadian_province_bc(): void {
		$this->rule->setParameters(['country' => 'CA']);
		$this->assertTrue($this->rule->check('BC'));
	}
}
