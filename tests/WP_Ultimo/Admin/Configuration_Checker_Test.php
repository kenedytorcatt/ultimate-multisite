<?php
/**
 * Tests for Configuration_Checker class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Admin;

use WP_UnitTestCase;

/**
 * Test class for Configuration_Checker.
 */
class Configuration_Checker_Test extends WP_UnitTestCase {

	/**
	 * @var Configuration_Checker
	 */
	private $checker;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->checker = Configuration_Checker::get_instance();
	}

	/**
	 * Test get_instance returns a Configuration_Checker instance.
	 */
	public function test_get_instance_returns_instance(): void {
		$this->assertInstanceOf(Configuration_Checker::class, $this->checker);
	}

	/**
	 * Test get_instance returns the same instance (singleton).
	 */
	public function test_get_instance_is_singleton(): void {
		$instance1 = Configuration_Checker::get_instance();
		$instance2 = Configuration_Checker::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test check_cookie_domain_configuration does not add notice when not network admin.
	 */
	public function test_check_cookie_domain_no_notice_outside_network_admin(): void {
		// Not in network admin context — method should return early.
		// We just verify it doesn't throw.
		$this->checker->check_cookie_domain_configuration();

		$this->assertTrue(true); // No exception thrown.
	}
}
