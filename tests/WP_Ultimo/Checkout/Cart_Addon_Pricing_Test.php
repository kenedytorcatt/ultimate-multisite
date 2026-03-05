<?php

namespace WP_Ultimo\Checkout;

use WP_Ultimo\Models\Customer;
use WP_Ultimo\Models\Membership;
use WP_Ultimo\Models\Product;
use WP_Ultimo\Models\Discount_Code;
use WP_Ultimo\Database\Memberships\Membership_Status;
use WP_UnitTestCase;

/**
 * Test class for Cart addon pricing functionality.
 *
 * Tests the fix for the bug where adding addon services to an existing membership
 * was incorrectly charging for the next billing period in advance.
 *
 * @group cart
 * @group checkout
 * @group addon-pricing
 */
class Cart_Addon_Pricing_Test extends WP_UnitTestCase {

	private static Customer $customer;
	private static Product $plan;
	private static Product $addon;
	private static Membership $membership;
	private static Discount_Code $discount_code;

	public static function set_up_before_class() {
		parent::set_up_before_class();

		// Create a test customer
		self::$customer = wu_create_customer([
			'username' => 'testuser_addon_pricing',
			'email'    => 'addon_pricing@example.com',
			'password' => 'password123',
		]);

		// Create a plan product (€90/month)
		self::$plan = wu_create_product([
			'name'          => 'Test Plan',
			'slug'          => 'test-plan-addon-pricing',
			'amount'        => 90.00,
			'duration'      => 1,
			'duration_unit' => 'month',
			'type'          => 'plan',
			'recurring'     => true,
			'setup_fee'     => 0,
		]);

		// Create an addon product (€5)
		self::$addon = wu_create_product([
			'name'          => 'Test Addon',
			'slug'          => 'test-addon-service',
			'amount'        => 5.00,
			'duration'      => 1,
			'duration_unit' => 'month',
			'type'          => 'service',
			'recurring'     => true,
			'setup_fee'     => 0,
		]);

		// Create a discount code (10% off, applies to renewals)
		self::$discount_code = wu_create_discount_code([
			'name'                => 'Test Discount',
			'code'                => 'TEST10',
			'value'               => 10,
			'type'                => 'percentage',
			'uses'                => 0,
			'max_uses'            => 100,
			'apply_to_renewals'   => true,
		]);

		// Create an active membership for the customer
		self::$membership = wu_create_membership([
			'customer_id'     => self::$customer->get_id(),
			'plan_id'         => self::$plan->get_id(),
			'amount'          => 90.00,
			'currency'        => 'EUR',
			'duration'        => 1,
			'duration_unit'   => 'month',
			'status'          => Membership_Status::ACTIVE,
			'times_billed'    => 1,
			'discount_code'   => self::$discount_code->get_code(),
			'date_created'    => wu_date()->modify('-15 days')->format('Y-m-d H:i:s'), // 15 days ago
			'date_renewed'    => wu_date()->modify('-15 days')->format('Y-m-d H:i:s'),
			'date_expiration' => wu_date()->modify('+15 days')->format('Y-m-d H:i:s'), // 15 days from now
		]);
	}

	/**
	 * Test that addon purchases only charge for the addon, not the existing plan.
	 *
	 * Bug: Previously, adding a €5 addon to a €90/month membership would charge ~€89.59
	 * (€90 plan + €5 addon - small pro-rata credit).
	 *
	 * Expected: Should only charge €5 for the addon.
	 */
	public function test_addon_only_charges_for_addon_product() {
		$cart = new Cart([
			'customer_id'   => self::$customer->get_id(),
			'membership_id' => self::$membership->get_id(),
			'products'      => [self::$addon->get_id()],
		]);

		// The cart should be type 'addon'
		$this->assertEquals('addon', $cart->get_cart_type(), 'Cart type should be "addon"');

		// The cart should NOT include the existing plan
		$line_items = $cart->get_line_items();
		$product_line_items = array_filter($line_items, function($item) {
			return $item->get_type() === 'product';
		});

		// Should only have 1 product line item (the addon)
		$this->assertCount(1, $product_line_items, 'Should only have 1 product line item (the addon)');

		// Verify it's the addon, not the plan
		$addon_line_item = reset($product_line_items);
		$this->assertEquals(self::$addon->get_id(), $addon_line_item->get_product_id(), 'Product should be the addon');

		// The subtotal should be €5.00 (addon price only)
		$this->assertEquals(5.00, $cart->get_subtotal(), 'Subtotal should be €5.00 (addon price only)');

		// There should be NO pro-rata credit line items
		$credit_line_items = array_filter($line_items, function($item) {
			return $item->get_type() === 'credit';
		});
		$this->assertCount(0, $credit_line_items, 'Should have NO pro-rata credit for addon-only purchases');

		// Total should be €5.00 (no taxes in this test)
		$this->assertEquals(5.00, $cart->get_total(), 'Total should be €5.00');
	}

	/**
	 * Test that existing discount codes are applied to addon purchases.
	 *
	 * Bug: Previously, discount codes from the membership were not being applied
	 * to addon purchases.
	 *
	 * Expected: The membership's discount code (10% off) should be applied to the addon.
	 */
	public function test_addon_applies_existing_discount_code() {
		$cart = new Cart([
			'customer_id'   => self::$customer->get_id(),
			'membership_id' => self::$membership->get_id(),
			'products'      => [self::$addon->get_id()],
		]);

		// The cart should have the discount code from the membership
		$discount_code = $cart->get_discount_code();
		$this->assertNotNull($discount_code, 'Discount code should be applied');
		$this->assertEquals('TEST10', $discount_code->get_code(), 'Should be the membership discount code');

		// The addon should have a discount applied (10% off €5 = €0.50)
		$line_items = $cart->get_line_items();
		$addon_line_item = null;
		foreach ($line_items as $item) {
			if ($item->get_type() === 'product' && $item->get_product_id() === self::$addon->get_id()) {
				$addon_line_item = $item;
				break;
			}
		}

		$this->assertNotNull($addon_line_item, 'Addon line item should exist');
		$this->assertEquals(0.50, $addon_line_item->get_discount_total(), 'Discount should be €0.50 (10% of €5)');
		$this->assertEquals(4.50, $addon_line_item->get_total(), 'Addon total should be €4.50 after discount');
	}

	/**
	 * Test that the filter 'wu_cart_addon_include_existing_plan' can override the default behavior.
	 *
	 * The filter defaults to false (don't include plan), but sites can set it to true
	 * if they need the old behavior for specific use cases.
	 */
	public function test_addon_filter_can_include_existing_plan() {
		// Add filter to force inclusion of existing plan
		add_filter('wu_cart_addon_include_existing_plan', '__return_true');

		$cart = new Cart([
			'customer_id'   => self::$customer->get_id(),
			'membership_id' => self::$membership->get_id(),
			'products'      => [self::$addon->get_id()],
		]);

		// Should have 2 product line items (plan + addon)
		$line_items = $cart->get_line_items();
		$product_line_items = array_filter($line_items, function($item) {
			return $item->get_type() === 'product';
		});

		$this->assertCount(2, $product_line_items, 'Should have 2 product line items when filter returns true');

		// Should have a pro-rata credit line item
		$credit_line_items = array_filter($line_items, function($item) {
			return $item->get_type() === 'credit';
		});
		$this->assertGreaterThan(0, count($credit_line_items), 'Should have pro-rata credit when plan is included');

		// Remove filter
		remove_filter('wu_cart_addon_include_existing_plan', '__return_true');
	}

	/**
	 * Test that plan upgrades still use pro-rata correctly.
	 *
	 * When changing plans (upgrade/downgrade), pro-rata SHOULD still be applied.
	 * The fix should only affect addon-only purchases.
	 */
	public function test_plan_upgrade_still_uses_prorate() {
		// Create a higher-tier plan
		$upgraded_plan = wu_create_product([
			'name'          => 'Premium Plan',
			'slug'          => 'premium-plan-addon-test',
			'amount'        => 150.00,
			'duration'      => 1,
			'duration_unit' => 'month',
			'type'          => 'plan',
			'recurring'     => true,
			'setup_fee'     => 0,
		]);

		$cart = new Cart([
			'customer_id'   => self::$customer->get_id(),
			'membership_id' => self::$membership->get_id(),
			'products'      => [$upgraded_plan->get_id()],
		]);

		// Cart type should be 'upgrade' (or 'downgrade')
		$this->assertContains($cart->get_cart_type(), ['upgrade', 'downgrade'], 'Cart type should be upgrade or downgrade');

		// Should have a pro-rata credit for plan changes
		$line_items = $cart->get_line_items();
		$credit_line_items = array_filter($line_items, function($item) {
			return $item->get_type() === 'credit';
		});

		$this->assertGreaterThan(0, count($credit_line_items), 'Plan upgrades should have pro-rata credit');

		// Clean up
		$upgraded_plan->delete();
	}

	/**
	 * Test that setup fees are not re-applied for addon purchases on existing memberships.
	 */
	public function test_addon_does_not_reapply_setup_fees() {
		// Create an addon with a setup fee
		$addon_with_fee = wu_create_product([
			'name'          => 'Addon with Fee',
			'slug'          => 'addon-with-setup-fee',
			'amount'        => 10.00,
			'duration'      => 1,
			'duration_unit' => 'month',
			'type'          => 'service',
			'recurring'     => true,
			'setup_fee'     => 20.00, // €20 setup fee
		]);

		$cart = new Cart([
			'customer_id'   => self::$customer->get_id(),
			'membership_id' => self::$membership->get_id(),
			'products'      => [$addon_with_fee->get_id()],
		]);

		// Get all line items
		$line_items = $cart->get_line_items();

		// Should have setup fee line item for the NEW addon (first time adding it)
		$fee_line_items = array_filter($line_items, function($item) use ($addon_with_fee) {
			return $item->get_type() === 'fee' && $item->get_product_id() === $addon_with_fee->get_id();
		});

		$this->assertCount(1, $fee_line_items, 'Should have 1 setup fee for the new addon');

		$fee_line_item = reset($fee_line_items);
		$this->assertEquals(20.00, $fee_line_item->get_unit_price(), 'Setup fee should be €20');

		// Clean up
		$addon_with_fee->delete();
	}

	public static function tear_down_after_class() {
		self::$membership->delete();
		self::$addon->delete();
		self::$plan->delete();
		self::$discount_code->delete();
		self::$customer->delete();
		parent::tear_down_after_class();
	}
}
