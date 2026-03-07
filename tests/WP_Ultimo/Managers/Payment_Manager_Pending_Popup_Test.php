<?php

namespace WP_Ultimo\Managers;

use WP_UnitTestCase;
use WP_Ultimo\Models\Customer;

/**
 * Regression tests for Payment_Manager::check_pending_payments() and
 * render_pending_payments().
 *
 * Ensures that pending and cancelled memberships (from abandoned checkouts)
 * do not trigger the "pending payment" popup on user login, which previously
 * pointed users at WC orders that may no longer exist.
 *
 * @see https://github.com/Ultimate-Multisite/ultimate-multisite/pull/360
 */
class Payment_Manager_Pending_Popup_Test extends WP_UnitTestCase {

	private Payment_Manager $manager;
	private Customer $customer;
	private \WP_User $wp_user;

	public function setUp(): void {

		parent::setUp();

		$uid = uniqid('popup_');

		$this->customer = wu_create_customer(
			[
				'username' => $uid,
				'email'    => $uid . '@example.com',
				'password' => 'password123',
			]
		);

		$this->wp_user = $this->customer->get_user();

		$this->manager = Payment_Manager::get_instance();

		delete_user_meta($this->wp_user->ID, 'wu_show_pending_payment_popup');
	}

	/**
	 * A pending membership (abandoned checkout) must NOT trigger the popup.
	 *
	 * Before the fix the loop did not skip pending memberships, so any
	 * abandoned checkout with a linked WU payment would silently set the meta
	 * on every subsequent login.
	 */
	public function test_pending_membership_does_not_trigger_popup(): void {

		$product = wu_create_product(
			[
				'name'   => 'Plan',
				'slug'   => 'plan-popup-pending-' . uniqid(),
				'amount' => 50.00,
				'type'   => 'plan',
				'active' => true,
			]
		);

		$membership = wu_create_membership(
			[
				'customer_id' => $this->customer->get_id(),
				'plan_id'     => $product->get_id(),
				'status'      => 'pending',
				'recurring'   => true,
			]
		);

		wu_create_payment(
			[
				'customer_id'   => $this->customer->get_id(),
				'membership_id' => $membership->get_id(),
				'status'        => 'pending',
				'total'         => 50.00,
				'gateway'       => 'woocommerce',
			]
		);

		$this->manager->check_pending_payments($this->wp_user);

		$this->assertEmpty(
			get_user_meta($this->wp_user->ID, 'wu_show_pending_payment_popup', true),
			'A pending membership must not trigger the pending payment popup.'
		);

		$membership->delete();
		$product->delete();
	}

	/**
	 * A cancelled membership must NOT trigger the popup.
	 */
	public function test_cancelled_membership_does_not_trigger_popup(): void {

		$product = wu_create_product(
			[
				'name'   => 'Plan',
				'slug'   => 'plan-popup-cancelled-' . uniqid(),
				'amount' => 50.00,
				'type'   => 'plan',
				'active' => true,
			]
		);

		$membership = wu_create_membership(
			[
				'customer_id' => $this->customer->get_id(),
				'plan_id'     => $product->get_id(),
				'status'      => 'cancelled',
				'recurring'   => true,
			]
		);

		wu_create_payment(
			[
				'customer_id'   => $this->customer->get_id(),
				'membership_id' => $membership->get_id(),
				'status'        => 'pending',
				'total'         => 50.00,
				'gateway'       => 'woocommerce',
			]
		);

		$this->manager->check_pending_payments($this->wp_user);

		$this->assertEmpty(
			get_user_meta($this->wp_user->ID, 'wu_show_pending_payment_popup', true),
			'A cancelled membership must not trigger the pending payment popup.'
		);

		$membership->delete();
		$product->delete();
	}

	/**
	 * An active membership with a genuine pending payment MUST trigger the popup.
	 * Validates that the skip only applies to pending/cancelled memberships and
	 * does not suppress legitimate payment reminders.
	 */
	public function test_active_membership_with_pending_payment_triggers_popup(): void {

		$product = wu_create_product(
			[
				'name'   => 'Plan',
				'slug'   => 'plan-popup-active-' . uniqid(),
				'amount' => 50.00,
				'type'   => 'plan',
				'active' => true,
			]
		);

		$membership = wu_create_membership(
			[
				'customer_id' => $this->customer->get_id(),
				'plan_id'     => $product->get_id(),
				'status'      => 'active',
				'recurring'   => true,
			]
		);

		wu_create_payment(
			[
				'customer_id'   => $this->customer->get_id(),
				'membership_id' => $membership->get_id(),
				'status'        => 'pending',
				'total'         => 50.00,
				'gateway'       => 'woocommerce',
			]
		);

		$this->manager->check_pending_payments($this->wp_user);

		$this->assertNotEmpty(
			get_user_meta($this->wp_user->ID, 'wu_show_pending_payment_popup', true),
			'An active membership with a pending payment must trigger the popup.'
		);

		$membership->delete();
		$product->delete();
	}

	public function tearDown(): void {

		global $wpdb;
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wu_memberships WHERE customer_id = %d", $this->customer->get_id()));
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wu_payments WHERE customer_id = %d", $this->customer->get_id()));
		delete_user_meta($this->wp_user->ID, 'wu_show_pending_payment_popup');
		$this->customer->delete();

		parent::tearDown();
	}
}
