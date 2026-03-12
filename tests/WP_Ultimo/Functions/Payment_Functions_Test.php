<?php
/**
 * Tests for payment functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for payment functions.
 */
class Payment_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_payment returns false for nonexistent.
	 */
	public function test_get_payment_nonexistent(): void {

		$result = wu_get_payment(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_payments returns array.
	 */
	public function test_get_payments_returns_array(): void {

		$result = wu_get_payments();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_payment_by_hash returns false for nonexistent.
	 */
	public function test_get_payment_by_hash_nonexistent(): void {

		$result = wu_get_payment_by_hash('nonexistent_hash');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_payment_by returns false for nonexistent.
	 */
	public function test_get_payment_by_nonexistent(): void {

		$result = wu_get_payment_by('id', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_line_item returns false for nonexistent payment.
	 */
	public function test_get_line_item_nonexistent_payment(): void {

		$result = wu_get_line_item('item_1', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_create_payment creates a payment.
	 */
	public function test_create_payment(): void {

		$user_id = self::factory()->user->create();

		$customer = wu_create_customer([
			'user_id'         => $user_id,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($customer);

		$product = wu_create_product([
			'name'            => 'Test Plan Payment',
			'slug'            => 'test-plan-payment-' . wp_rand(),
			'type'            => 'plan',
			'amount'          => 49.99,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($product);

		$membership = wu_create_membership([
			'customer_id'     => $customer->get_id(),
			'plan_id'         => $product->get_id(),
			'status'          => 'active',
			'amount'          => 49.99,
			'currency'        => 'USD',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($membership);

		$payment = wu_create_payment([
			'customer_id'     => $customer->get_id(),
			'membership_id'   => $membership->get_id(),
			'product_id'      => $product->get_id(),
			'currency'        => 'USD',
			'subtotal'        => 49.99,
			'total'           => 49.99,
			'status'          => 'completed',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($payment);
		$this->assertInstanceOf(\WP_Ultimo\Models\Payment::class, $payment);
	}

	/**
	 * Test wu_create_payment with save=false returns unsaved payment.
	 */
	public function test_create_payment_no_save(): void {

		$payment = wu_create_payment([
			'currency' => 'USD',
			'subtotal' => 10.00,
			'total'    => 10.00,
		], false);

		$this->assertInstanceOf(\WP_Ultimo\Models\Payment::class, $payment);
		$this->assertEmpty($payment->get_id());
	}

	/**
	 * Test wu_get_refundable_payment_types returns array.
	 */
	public function test_get_refundable_payment_types(): void {

		$result = wu_get_refundable_payment_types();

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
	}
}
