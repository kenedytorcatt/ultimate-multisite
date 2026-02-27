<?php
/**
 * Tests for PayPal REST Webhook Handler.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 * @since 2.x.x
 */

namespace WP_Ultimo\Gateways;

use WP_UnitTestCase;
use WP_Ultimo\Database\Payments\Payment_Status;
use WP_Ultimo\Database\Memberships\Membership_Status;

/**
 * PayPal Webhook Handler Test class.
 */
class PayPal_Webhook_Handler_Test extends WP_UnitTestCase {

	/**
	 * Test handler instance.
	 *
	 * @var PayPal_Webhook_Handler
	 */
	protected $handler;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		wu_save_setting('paypal_rest_sandbox_mode', 1);

		$this->handler = PayPal_Webhook_Handler::get_instance();
		$this->handler->init();
	}

	/**
	 * Test handler is singleton.
	 */
	public function test_singleton(): void {

		$instance1 = PayPal_Webhook_Handler::get_instance();
		$instance2 = PayPal_Webhook_Handler::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test handler initialization registers webhook action.
	 */
	public function test_init_registers_webhook_action(): void {

		$this->assertNotFalse(has_action('wu_paypal-rest_process_webhooks', [$this->handler, 'process_webhook']));
	}

	/**
	 * Test API base URL in sandbox mode.
	 */
	public function test_api_base_url_sandbox(): void {

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('get_api_base_url');

		$url = $method->invoke($this->handler);
		$this->assertEquals('https://api-m.sandbox.paypal.com', $url);
	}

	/**
	 * Test API base URL in live mode.
	 */
	public function test_api_base_url_live(): void {

		wu_save_setting('paypal_rest_sandbox_mode', 0);

		$handler = PayPal_Webhook_Handler::get_instance();
		$handler->init();

		$reflection = new \ReflectionClass($handler);
		$method = $reflection->getMethod('get_api_base_url');

		$url = $method->invoke($handler);
		$this->assertEquals('https://api-m.paypal.com', $url);
	}

	/**
	 * Test verify_webhook_signature returns false with missing headers.
	 */
	public function test_verify_signature_fails_without_headers(): void {

		// Clear any server headers
		unset(
			$_SERVER['HTTP_PAYPAL_AUTH_ALGO'],
			$_SERVER['HTTP_PAYPAL_CERT_URL'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']
		);

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('verify_webhook_signature');

		$result = $method->invoke($this->handler, '{"test": true}');
		$this->assertFalse($result);
	}

	/**
	 * Test verify_webhook_signature with skip filter.
	 */
	public function test_verify_signature_skip_with_filter(): void {

		// Clear server headers
		unset(
			$_SERVER['HTTP_PAYPAL_AUTH_ALGO'],
			$_SERVER['HTTP_PAYPAL_CERT_URL'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']
		);

		add_filter('wu_paypal_skip_webhook_verification', '__return_true');

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('verify_webhook_signature');

		$result = $method->invoke($this->handler, '{"test": true}');
		$this->assertTrue($result);

		remove_filter('wu_paypal_skip_webhook_verification', '__return_true');
	}

	/**
	 * Test verify_webhook_signature fails without webhook_id.
	 */
	public function test_verify_signature_fails_without_webhook_id(): void {

		// Set headers but no webhook ID
		$_SERVER['HTTP_PAYPAL_AUTH_ALGO'] = 'SHA256withRSA';
		$_SERVER['HTTP_PAYPAL_CERT_URL'] = 'https://api.sandbox.paypal.com/cert.pem';
		$_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] = 'trans-123';
		$_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] = 'sig-abc';
		$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] = '2026-01-01T00:00:00Z';

		wu_save_setting('paypal_rest_sandbox_webhook_id', '');

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('verify_webhook_signature');

		$result = $method->invoke($this->handler, '{"test": true}');
		$this->assertFalse($result);

		// Cleanup
		unset(
			$_SERVER['HTTP_PAYPAL_AUTH_ALGO'],
			$_SERVER['HTTP_PAYPAL_CERT_URL'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'],
			$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']
		);
	}

	/**
	 * Test get_membership_by_subscription returns null for empty ID.
	 */
	public function test_get_membership_by_subscription_empty(): void {

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('get_membership_by_subscription');

		$result = $method->invoke($this->handler, '');
		$this->assertNull($result);
	}

	/**
	 * Test get_membership_by_subscription returns null/false for non-existent subscription.
	 */
	public function test_get_membership_by_subscription_not_found(): void {

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('get_membership_by_subscription');

		$result = $method->invoke($this->handler, 'I-NONEXISTENT123');
		$this->assertEmpty($result);
	}

	/**
	 * Test handle_subscription_activated with valid membership.
	 */
	public function test_handle_subscription_activated(): void {

		// Create test data
		$customer = wu_create_customer([
			'user_id'    => self::factory()->user->create(),
			'email'      => 'paypal-test@example.com',
			'username'   => 'paypaltest',
		]);

		$product = wu_create_product([
			'name'            => 'PayPal Test Plan',
			'slug'            => 'paypal-test-plan',
			'type'            => 'plan',
			'amount'          => 29.99,
			'recurring'       => true,
			'duration'        => 1,
			'duration_unit'   => 'month',
			'currency'        => 'USD',
			'list_order'      => 0,
			'pricing_type'    => 'paid',
			'feature_list'    => [],
		]);

		$membership = wu_create_membership([
			'customer_id'             => $customer->get_id(),
			'plan_id'                 => $product->get_id(),
			'gateway'                 => 'paypal-rest',
			'gateway_subscription_id' => 'I-TESTACTIVATE',
			'status'                  => Membership_Status::PENDING,
			'amount'                  => 29.99,
			'currency'                => 'USD',
		]);

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('handle_subscription_activated');

		$method->invoke($this->handler, ['id' => 'I-TESTACTIVATE']);

		// Reload membership
		$membership = wu_get_membership($membership->get_id());
		$this->assertEquals(Membership_Status::ACTIVE, $membership->get_status());
	}

	/**
	 * Test handle_subscription_cancelled sets auto_renew to false.
	 */
	public function test_handle_subscription_cancelled(): void {

		$customer = wu_create_customer([
			'user_id'    => self::factory()->user->create(),
			'email'      => 'paypal-cancel@example.com',
			'username'   => 'paypalcancel',
		]);

		$product = wu_create_product([
			'name'            => 'PayPal Cancel Plan',
			'slug'            => 'paypal-cancel-plan',
			'type'            => 'plan',
			'amount'          => 19.99,
			'recurring'       => true,
			'duration'        => 1,
			'duration_unit'   => 'month',
			'currency'        => 'USD',
			'list_order'      => 0,
			'pricing_type'    => 'paid',
			'feature_list'    => [],
		]);

		$membership = wu_create_membership([
			'customer_id'             => $customer->get_id(),
			'plan_id'                 => $product->get_id(),
			'gateway'                 => 'paypal-rest',
			'gateway_subscription_id' => 'I-TESTCANCEL',
			'status'                  => Membership_Status::ACTIVE,
			'auto_renew'              => true,
			'amount'                  => 19.99,
			'currency'                => 'USD',
		]);

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('handle_subscription_cancelled');

		$method->invoke($this->handler, ['id' => 'I-TESTCANCEL']);

		// Reload membership
		$membership = wu_get_membership($membership->get_id());
		$this->assertFalse($membership->should_auto_renew());
	}

	/**
	 * Test handle_payment_completed creates renewal payment and prevents duplicates.
	 */
	public function test_handle_payment_completed_creates_renewal(): void {

		$customer = wu_create_customer([
			'user_id'    => self::factory()->user->create(),
			'email'      => 'paypal-renew@example.com',
			'username'   => 'paypalrenew',
		]);

		$product = wu_create_product([
			'name'            => 'PayPal Renewal Plan',
			'slug'            => 'paypal-renewal-plan',
			'type'            => 'plan',
			'amount'          => 49.99,
			'recurring'       => true,
			'duration'        => 1,
			'duration_unit'   => 'month',
			'currency'        => 'USD',
			'list_order'      => 0,
			'pricing_type'    => 'paid',
			'feature_list'    => [],
		]);

		$membership = wu_create_membership([
			'customer_id'             => $customer->get_id(),
			'plan_id'                 => $product->get_id(),
			'gateway'                 => 'paypal-rest',
			'gateway_subscription_id' => 'I-TESTRENEWAL',
			'status'                  => Membership_Status::ACTIVE,
			'amount'                  => 49.99,
			'currency'                => 'USD',
		]);

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('handle_payment_completed');

		$resource = [
			'id'                   => 'SALE-12345',
			'billing_agreement_id' => 'I-TESTRENEWAL',
			'amount'               => [
				'total'    => '49.99',
				'currency' => 'USD',
			],
		];

		$method->invoke($this->handler, $resource);

		// Check that a payment was created
		$payment = wu_get_payment_by('gateway_payment_id', 'SALE-12345');
		$this->assertNotNull($payment);
		$this->assertEquals(Payment_Status::COMPLETED, $payment->get_status());
		$this->assertEquals('paypal-rest', $payment->get_gateway());

		// Call again with same sale ID — should not create duplicate
		$method->invoke($this->handler, $resource);

		// Still only one payment
		$all_payments = wu_get_payments([
			'gateway_payment_id' => 'SALE-12345',
		]);
		$this->assertCount(1, $all_payments);
	}

	/**
	 * Test handle_subscription_suspended sets membership on hold.
	 */
	public function test_handle_subscription_suspended(): void {

		$customer = wu_create_customer([
			'user_id'    => self::factory()->user->create(),
			'email'      => 'paypal-suspend@example.com',
			'username'   => 'paypalsuspend',
		]);

		$product = wu_create_product([
			'name'            => 'PayPal Suspend Plan',
			'slug'            => 'paypal-suspend-plan',
			'type'            => 'plan',
			'amount'          => 9.99,
			'recurring'       => true,
			'duration'        => 1,
			'duration_unit'   => 'month',
			'currency'        => 'USD',
			'list_order'      => 0,
			'pricing_type'    => 'paid',
			'feature_list'    => [],
		]);

		$membership = wu_create_membership([
			'customer_id'             => $customer->get_id(),
			'plan_id'                 => $product->get_id(),
			'gateway'                 => 'paypal-rest',
			'gateway_subscription_id' => 'I-TESTSUSPEND',
			'status'                  => Membership_Status::ACTIVE,
			'amount'                  => 9.99,
			'currency'                => 'USD',
		]);

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('handle_subscription_suspended');

		$method->invoke($this->handler, ['id' => 'I-TESTSUSPEND']);

		// Reload membership
		$membership = wu_get_membership($membership->get_id());
		$this->assertEquals(Membership_Status::ON_HOLD, $membership->get_status());
	}

	/**
	 * Test handle_capture_refunded marks payment as refunded.
	 */
	public function test_handle_capture_refunded(): void {

		$customer = wu_create_customer([
			'user_id'    => self::factory()->user->create(),
			'email'      => 'paypal-refund@example.com',
			'username'   => 'paypalrefund',
		]);

		$product = wu_create_product([
			'name'            => 'PayPal Refund Plan',
			'slug'            => 'paypal-refund-plan',
			'type'            => 'plan',
			'amount'          => 59.99,
			'recurring'       => false,
			'duration'        => 0,
			'duration_unit'   => 'month',
			'currency'        => 'USD',
			'list_order'      => 0,
			'pricing_type'    => 'paid',
			'feature_list'    => [],
		]);

		$membership = wu_create_membership([
			'customer_id' => $customer->get_id(),
			'plan_id'     => $product->get_id(),
			'gateway'     => 'paypal-rest',
			'status'      => Membership_Status::ACTIVE,
			'amount'      => 59.99,
			'currency'    => 'USD',
		]);

		$payment = wu_create_payment([
			'customer_id'        => $customer->get_id(),
			'membership_id'      => $membership->get_id(),
			'gateway'            => 'paypal-rest',
			'gateway_payment_id' => '8MC585209K746631H',
			'status'             => Payment_Status::COMPLETED,
			'total'              => 59.99,
			'subtotal'           => 59.99,
			'currency'           => 'USD',
			'product_id'         => $product->get_id(),
		]);

		$reflection = new \ReflectionClass($this->handler);
		$method = $reflection->getMethod('handle_capture_refunded');

		$resource = [
			'id'     => 'REFUND-456',
			'amount' => ['value' => '59.99'],
			'links'  => [
				[
					'rel'  => 'up',
					'href' => 'https://api.sandbox.paypal.com/v2/payments/captures/8MC585209K746631H',
				],
			],
		];

		$method->invoke($this->handler, $resource);

		// Reload payment
		$payment = wu_get_payment($payment->get_id());
		$this->assertEquals(Payment_Status::REFUND, $payment->get_status());
	}

	/**
	 * Cleanup after all tests.
	 */
	public static function tear_down_after_class(): void {
		global $wpdb;

		$wpdb->query("TRUNCATE TABLE {$wpdb->base_prefix}wu_memberships");
		$wpdb->query("TRUNCATE TABLE {$wpdb->base_prefix}wu_payments");
		$wpdb->query("TRUNCATE TABLE {$wpdb->base_prefix}wu_customers");
		$wpdb->query("TRUNCATE TABLE {$wpdb->base_prefix}wu_products");

		parent::tear_down_after_class();
	}
}
