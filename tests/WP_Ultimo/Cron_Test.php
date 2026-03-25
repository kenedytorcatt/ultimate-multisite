<?php
/**
 * Tests for Cron class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

use WP_UnitTestCase;

/**
 * Test class for Cron.
 */
class Cron_Test extends WP_UnitTestCase {

	/**
	 * @var Cron
	 */
	private Cron $cron;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {

		parent::set_up();
		$this->cron = Cron::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Cron::class, $this->cron);
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(Cron::get_instance(), Cron::get_instance());
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$this->cron->init();

		$this->assertGreaterThan(0, has_action('init', [$this->cron, 'create_schedules']));
		$this->assertGreaterThan(0, has_action('init', [$this->cron, 'schedule_membership_check']));
		$this->assertGreaterThan(0, has_action('wu_membership_check', [$this->cron, 'membership_renewal_check']));
		$this->assertGreaterThan(0, has_action('wu_membership_check', [$this->cron, 'membership_trial_check']));
		$this->assertGreaterThan(0, has_action('wu_membership_check', [$this->cron, 'membership_expired_check']));
	}

	/**
	 * Test membership_renewal_check runs without error.
	 */
	public function test_membership_renewal_check_no_memberships(): void {

		// Should not throw with no memberships.
		$this->cron->membership_renewal_check();

		$this->assertTrue(true); // No exception thrown.
	}

	/**
	 * Test membership_trial_check runs without error.
	 */
	public function test_membership_trial_check_no_memberships(): void {

		$this->cron->membership_trial_check();

		$this->assertTrue(true);
	}

	/**
	 * Test membership_expired_check runs without error.
	 */
	public function test_membership_expired_check_no_memberships(): void {

		$this->cron->membership_expired_check();

		$this->assertTrue(true);
	}

	/**
	 * Test async_create_renewal_payment with invalid membership.
	 */
	public function test_async_create_renewal_payment_invalid(): void {

		// Should return early with no error for nonexistent membership.
		$this->cron->async_create_renewal_payment(999999);

		$this->assertTrue(true);
	}

	/**
	 * Test async_mark_membership_as_expired with invalid membership.
	 */
	public function test_async_mark_membership_as_expired_invalid(): void {

		$this->cron->async_mark_membership_as_expired(999999);

		$this->assertTrue(true);
	}
}
