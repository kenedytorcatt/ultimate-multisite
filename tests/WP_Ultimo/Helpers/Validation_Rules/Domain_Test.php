<?php
/**
 * Tests for Domain validation rule.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Helpers\Validation_Rules;

use WP_UnitTestCase;

/**
 * Test class for Domain validation rule.
 */
class Domain_Test extends WP_UnitTestCase {

	/**
	 * @var Domain
	 */
	private $rule;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->rule = new Domain();
	}

	/**
	 * Test valid domain with com TLD.
	 */
	public function test_valid_domain_com(): void {
		$this->assertTrue($this->rule->check('example.com'));
	}

	/**
	 * Test valid domain with org TLD.
	 */
	public function test_valid_domain_org(): void {
		$this->assertTrue($this->rule->check('example.org'));
	}

	/**
	 * Test valid domain with net TLD.
	 */
	public function test_valid_domain_net(): void {
		$this->assertTrue($this->rule->check('example.net'));
	}

	/**
	 * Test valid domain with subdomain.
	 */
	public function test_valid_domain_with_subdomain(): void {
		$this->assertTrue($this->rule->check('sub.example.com'));
	}

	/**
	 * Test valid domain with multiple subdomains.
	 */
	public function test_valid_domain_multiple_subdomains(): void {
		$this->assertTrue($this->rule->check('deep.sub.example.com'));
	}

	/**
	 * Test valid domain with hyphen.
	 */
	public function test_valid_domain_with_hyphen(): void {
		$this->assertTrue($this->rule->check('my-site.com'));
	}

	/**
	 * Test valid domain with numbers.
	 */
	public function test_valid_domain_with_numbers(): void {
		$this->assertTrue($this->rule->check('site123.com'));
	}

	/**
	 * Test valid domain with country TLD.
	 */
	public function test_valid_domain_country_tld(): void {
		$this->assertTrue($this->rule->check('example.co.uk'));
	}

	/**
	 * Test invalid domain starting with hyphen.
	 */
	public function test_invalid_domain_starts_with_hyphen(): void {
		$this->assertFalse($this->rule->check('-example.com'));
	}

	/**
	 * Test invalid domain without TLD.
	 */
	public function test_invalid_domain_no_tld(): void {
		$this->assertFalse($this->rule->check('example'));
	}

	/**
	 * Test invalid domain with spaces.
	 */
	public function test_invalid_domain_with_spaces(): void {
		$this->assertFalse($this->rule->check('example site.com'));
	}

	/**
	 * Test invalid domain with underscore.
	 */
	public function test_invalid_domain_with_underscore(): void {
		$this->assertFalse($this->rule->check('example_site.com'));
	}

	/**
	 * Test invalid domain with special characters.
	 */
	public function test_invalid_domain_special_chars(): void {
		$this->assertFalse($this->rule->check('example!@#.com'));
	}

	/**
	 * Test empty string is invalid.
	 */
	public function test_invalid_empty_string(): void {
		$this->assertFalse($this->rule->check(''));
	}

	/**
	 * Test domain ending with dot is invalid.
	 */
	public function test_invalid_domain_ending_with_dot(): void {
		$this->assertFalse($this->rule->check('example.com.'));
	}

	/**
	 * Test valid domain with new gTLD.
	 */
	public function test_valid_domain_new_gtld(): void {
		$this->assertTrue($this->rule->check('example.dev'));
	}

	/**
	 * Test valid domain with long TLD.
	 */
	public function test_valid_domain_long_tld(): void {
		$this->assertTrue($this->rule->check('example.technology'));
	}

	/**
	 * Test message property exists.
	 */
	public function test_message_property(): void {
		$reflection = new \ReflectionClass($this->rule);
		$property   = $reflection->getProperty('message');
		$property->setAccessible(true);

		$this->assertIsString($property->getValue($this->rule));
	}
}
