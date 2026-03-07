<?php

namespace WP_Ultimo\Models;

use WP_UnitTestCase;

/**
 * Regression tests for Customer::has_trialed().
 *
 * Ensures that abandoned checkouts (pending memberships that have date_trial_end
 * set before payment is collected) do not permanently block a customer from
 * receiving a free trial.
 *
 * @see https://github.com/Ultimate-Multisite/ultimate-multisite/pull/360
 */
class Customer_Has_Trialed_Test extends WP_UnitTestCase {

	private static Customer $customer;
	private static Product $product;

	public static function set_up_before_class(): void {

		parent::set_up_before_class();

		self::$customer = wu_create_customer(
			[
				'username' => 'has_trialed_test_user',
				'email'    => 'has_trialed_test@example.com',
				'password' => 'password123',
			]
		);

		self::$product = wu_create_product(
			[
				'name'                => 'Trialed Product',
				'slug'                => 'trialed-product',
				'amount'              => 10.00,
				'recurring'           => true,
				'duration'            => 1,
				'duration_unit'       => 'month',
				'trial_duration'      => 14,
				'trial_duration_unit' => 'day',
				'type'                => 'plan',
				'pricing_type'        => 'paid',
				'active'              => true,
			]
		);
	}

	/**
	 * Clear cached trial meta before each test so results don't bleed between cases.
	 */
	public function setUp(): void {

		parent::setUp();

		self::$customer->delete_meta(Customer::META_HAS_TRIALED);
	}

	/**
	 * THE BUG (pre-fix): has_trialed() matched a pending membership (created at
	 * form submit before payment) and permanently blocked future trials.
	 *
	 * Reproduces the exact production sequence:
	 *  1. User submits checkout → WP Ultimo creates membership in 'pending' with
	 *     date_trial_end already set.
	 *  2. User abandons (closes tab / navigates away) without paying.
	 *  3. User returns and tries to check out again.
	 *  4. has_trialed() must return false so they still get their trial.
	 */
	public function test_pending_membership_with_trial_does_not_block_future_trial(): void {

		// Membership created in pending status without a trial date first (avoids
		// the save() auto-transition to trialing that fires when date_trial_end is
		// in the future AND no pending payment exists yet).
		$membership = wu_create_membership(
			[
				'customer_id' => self::$customer->get_id(),
				'plan_id'     => self::$product->get_id(),
				'status'      => 'pending',
				'recurring'   => true,
			]
		);

		// WP Ultimo sets date_trial_end at form submit (before payment is collected).
		// Replicate that by injecting the value directly into the DB row.
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wu_memberships',
			['date_trial_end' => gmdate('Y-m-d H:i:s', strtotime('+14 days'))],
			['id' => $membership->get_id()]
		);

		// Load a fresh customer instance so there is no internal cache from above.
		$fresh = wu_get_customer(self::$customer->get_id());

		$this->assertFalse(
			(bool) $fresh->has_trialed(),
			'A pending membership from an abandoned checkout must NOT count as a used trial.'
		);

		$membership->delete();
	}

	/**
	 * An active membership with a trial end date must still count as trialed.
	 * Validates that the fix does not break the normal happy-path scenario.
	 */
	public function test_active_membership_with_trial_counts_as_trialed(): void {

		$membership = wu_create_membership(
			[
				'customer_id' => self::$customer->get_id(),
				'plan_id'     => self::$product->get_id(),
				'status'      => 'active',
				'recurring'   => true,
			]
		);

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wu_memberships',
			['date_trial_end' => gmdate('Y-m-d H:i:s', strtotime('+14 days'))],
			['id' => $membership->get_id()]
		);

		$fresh = wu_get_customer(self::$customer->get_id());

		$this->assertTrue(
			(bool) $fresh->has_trialed(),
			'An active membership with date_trial_end set must count as a used trial.'
		);

		self::$customer->delete_meta(Customer::META_HAS_TRIALED);
		$membership->delete();
	}

	/**
	 * A trialing membership must count as trialed.
	 */
	public function test_trialing_membership_counts_as_trialed(): void {

		$membership = wu_create_membership(
			[
				'customer_id' => self::$customer->get_id(),
				'plan_id'     => self::$product->get_id(),
				'status'      => 'trialing',
				'recurring'   => true,
			]
		);

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wu_memberships',
			['date_trial_end' => gmdate('Y-m-d H:i:s', strtotime('+14 days'))],
			['id' => $membership->get_id()]
		);

		$fresh = wu_get_customer(self::$customer->get_id());

		$this->assertTrue(
			(bool) $fresh->has_trialed(),
			'A trialing membership must count as a used trial.'
		);

		self::$customer->delete_meta(Customer::META_HAS_TRIALED);
		$membership->delete();
	}

	/**
	 * A cancelled membership that went through a genuine trial (date_trial_end
	 * still set in the DB) must continue to block a second trial.
	 *
	 * This validates the fix is scoped to 'pending' only — not 'cancelled' —
	 * so users who cancel after actually using a trial cannot get another free one.
	 */
	public function test_cancelled_membership_after_genuine_trial_still_blocks_second_trial(): void {

		$membership = wu_create_membership(
			[
				'customer_id' => self::$customer->get_id(),
				'plan_id'     => self::$product->get_id(),
				'status'      => 'cancelled',
				'recurring'   => true,
			]
		);

		// Trial was consumed — date_trial_end is in the past but still set.
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wu_memberships',
			['date_trial_end' => gmdate('Y-m-d H:i:s', strtotime('-1 day'))],
			['id' => $membership->get_id()]
		);

		$fresh = wu_get_customer(self::$customer->get_id());

		$this->assertTrue(
			(bool) $fresh->has_trialed(),
			'A cancelled membership with date_trial_end still set must block a second trial.'
		);

		self::$customer->delete_meta(Customer::META_HAS_TRIALED);
		$membership->delete();
	}

	/**
	 * After the hourly cleanup processes an orphaned checkout it cancels the
	 * membership AND clears date_trial_end to NULL. In that state the customer
	 * must be free to get a trial on their next checkout attempt.
	 */
	public function test_cleaned_orphan_does_not_block_future_trial(): void {

		// Cancelled + date_trial_end NULL (default) — this is the state left by
		// the cleanup fix in class-woocommerce-gateway.php.
		$membership = wu_create_membership(
			[
				'customer_id' => self::$customer->get_id(),
				'plan_id'     => self::$product->get_id(),
				'status'      => 'cancelled',
				'recurring'   => true,
			]
		);

		$fresh = wu_get_customer(self::$customer->get_id());

		$this->assertFalse(
			(bool) $fresh->has_trialed(),
			'A cleaned-up orphaned membership (cancelled + date_trial_end cleared) must NOT block future trials.'
		);

		$membership->delete();
	}

	public static function tear_down_after_class(): void {

		self::$customer->delete();
		self::$product->delete();

		parent::tear_down_after_class();
	}
}
