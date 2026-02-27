<?php
/**
 * Tests for PayPal REST Gateway.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 * @since 2.x.x
 */

namespace WP_Ultimo\Gateways;

use WP_UnitTestCase;

/**
 * PayPal REST Gateway Test class.
 */
class PayPal_REST_Gateway_Test extends WP_UnitTestCase {

	/**
	 * Test gateway instance.
	 *
	 * @var PayPal_REST_Gateway
	 */
	protected $gateway;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clear all PayPal REST settings before each test
		wu_save_setting('paypal_rest_sandbox_mode', 1);
		wu_save_setting('paypal_rest_sandbox_client_id', '');
		wu_save_setting('paypal_rest_sandbox_client_secret', '');
		wu_save_setting('paypal_rest_sandbox_merchant_id', '');
		wu_save_setting('paypal_rest_live_client_id', '');
		wu_save_setting('paypal_rest_live_client_secret', '');
		wu_save_setting('paypal_rest_live_merchant_id', '');
		wu_save_setting('paypal_rest_connected', false);
		wu_save_setting('paypal_rest_sandbox_webhook_id', '');
		wu_save_setting('paypal_rest_live_webhook_id', '');

		$this->gateway = new PayPal_REST_Gateway();
	}

	/**
	 * Test gateway ID.
	 */
	public function test_gateway_id(): void {

		$this->assertEquals('paypal-rest', $this->gateway->get_id());
	}

	/**
	 * Test gateway supports recurring.
	 */
	public function test_supports_recurring(): void {

		$this->assertTrue($this->gateway->supports_recurring());
	}

	/**
	 * Test gateway supports amount update.
	 */
	public function test_supports_amount_update(): void {

		$this->assertTrue($this->gateway->supports_amount_update());
	}

	/**
	 * Test not configured when no credentials.
	 */
	public function test_not_configured_without_credentials(): void {

		$this->gateway->init();
		$this->assertFalse($this->gateway->is_configured());
	}

	/**
	 * Test configured with manual client credentials.
	 */
	public function test_configured_with_manual_credentials(): void {

		wu_save_setting('paypal_rest_sandbox_client_id', 'test_client_id_123');
		wu_save_setting('paypal_rest_sandbox_client_secret', 'test_client_secret_456');
		wu_save_setting('paypal_rest_sandbox_mode', 1);

		$gateway = new PayPal_REST_Gateway();
		$gateway->init();

		$this->assertTrue($gateway->is_configured());
	}

	/**
	 * Test configured with OAuth merchant ID.
	 */
	public function test_configured_with_oauth_merchant_id(): void {

		wu_save_setting('paypal_rest_sandbox_merchant_id', 'MERCHANT123');
		wu_save_setting('paypal_rest_sandbox_mode', 1);

		$gateway = new PayPal_REST_Gateway();
		$gateway->init();

		$this->assertTrue($gateway->is_configured());
	}

	/**
	 * Test connection status when not connected.
	 */
	public function test_connection_status_not_connected(): void {

		$this->gateway->init();
		$status = $this->gateway->get_connection_status();

		$this->assertFalse($status['connected']);
		$this->assertArrayHasKey('message', $status);
		$this->assertArrayHasKey('details', $status);
	}

	/**
	 * Test connection status with manual credentials.
	 */
	public function test_connection_status_with_manual_credentials(): void {

		wu_save_setting('paypal_rest_sandbox_client_id', 'test_client_id_123');
		wu_save_setting('paypal_rest_sandbox_client_secret', 'test_client_secret_456');
		wu_save_setting('paypal_rest_sandbox_mode', 1);

		$gateway = new PayPal_REST_Gateway();
		$gateway->init();
		$status = $gateway->get_connection_status();

		$this->assertTrue($status['connected']);
		$this->assertEquals('manual', $status['details']['method']);
		$this->assertEquals('sandbox', $status['details']['mode']);
	}

	/**
	 * Test connection status with OAuth merchant.
	 */
	public function test_connection_status_with_oauth(): void {

		wu_save_setting('paypal_rest_sandbox_merchant_id', 'MERCHANT123');
		wu_save_setting('paypal_rest_sandbox_merchant_email', 'merchant@example.com');
		wu_save_setting('paypal_rest_sandbox_mode', 1);
		wu_save_setting('paypal_rest_connection_date', '2026-01-01 00:00:00');

		$gateway = new PayPal_REST_Gateway();
		$gateway->init();
		$status = $gateway->get_connection_status();

		$this->assertTrue($status['connected']);
		$this->assertEquals('oauth', $status['details']['method']);
		$this->assertEquals('MERCHANT123', $status['details']['merchant_id']);
	}

	/**
	 * Test set_test_mode switches credentials.
	 */
	public function test_set_test_mode(): void {

		wu_save_setting('paypal_rest_sandbox_client_id', 'sandbox_id');
		wu_save_setting('paypal_rest_sandbox_client_secret', 'sandbox_secret');
		wu_save_setting('paypal_rest_live_client_id', 'live_id');
		wu_save_setting('paypal_rest_live_client_secret', 'live_secret');
		wu_save_setting('paypal_rest_sandbox_mode', 1);

		$gateway = new PayPal_REST_Gateway();
		$gateway->init();

		// Verify sandbox mode credentials
		$reflection = new \ReflectionClass($gateway);
		$prop = $reflection->getProperty('client_id');

		$this->assertEquals('sandbox_id', $prop->getValue($gateway));

		// Switch to live mode
		$gateway->set_test_mode(false);

		$this->assertEquals('live_id', $prop->getValue($gateway));
	}

	/**
	 * Test payment URL generation.
	 */
	public function test_payment_url_on_gateway(): void {

		$this->gateway->init();

		$url = $this->gateway->get_payment_url_on_gateway('PAY-123');
		$this->assertStringContainsString('sandbox.paypal.com', $url);
		$this->assertStringContainsString('PAY-123', $url);
	}

	/**
	 * Test payment URL empty for empty ID.
	 */
	public function test_payment_url_empty_for_empty_id(): void {

		$this->gateway->init();

		$url = $this->gateway->get_payment_url_on_gateway('');
		$this->assertEmpty($url);
	}

	/**
	 * Test subscription URL for REST API subscription.
	 */
	public function test_subscription_url_rest_api(): void {

		$this->gateway->init();

		$url = $this->gateway->get_subscription_url_on_gateway('I-SUBSCRIPTION123');
		$this->assertStringContainsString('sandbox.paypal.com', $url);
		$this->assertStringContainsString('billing/subscriptions', $url);
		$this->assertStringContainsString('I-SUBSCRIPTION123', $url);
	}

	/**
	 * Test subscription URL for legacy NVP profile.
	 */
	public function test_subscription_url_legacy_nvp(): void {

		$this->gateway->init();

		$url = $this->gateway->get_subscription_url_on_gateway('LEGACY-PROFILE-123');
		$this->assertStringContainsString('sandbox.paypal.com', $url);
		$this->assertStringContainsString('_profile-recurring-payments', $url);
	}

	/**
	 * Test subscription URL empty for empty ID.
	 */
	public function test_subscription_url_empty_for_empty_id(): void {

		$this->gateway->init();

		$url = $this->gateway->get_subscription_url_on_gateway('');
		$this->assertEmpty($url);
	}

	/**
	 * Test live mode URLs.
	 */
	public function test_live_mode_urls(): void {

		wu_save_setting('paypal_rest_sandbox_mode', 0);
		wu_save_setting('paypal_rest_live_client_id', 'live_id');
		wu_save_setting('paypal_rest_live_client_secret', 'live_secret');

		$gateway = new PayPal_REST_Gateway();
		$gateway->init();

		$url = $gateway->get_payment_url_on_gateway('PAY-123');
		$this->assertStringContainsString('www.paypal.com', $url);
		$this->assertStringNotContainsString('sandbox', $url);
	}

	/**
	 * Test other_ids includes both paypal and paypal-rest.
	 */
	public function test_other_ids(): void {

		$all_ids = $this->gateway->get_all_ids();

		$this->assertContains('paypal-rest', $all_ids);
		$this->assertContains('paypal', $all_ids);
	}

	/**
	 * Test API base URL in sandbox mode.
	 */
	public function test_api_base_url_sandbox(): void {

		$this->gateway->init();

		$reflection = new \ReflectionClass($this->gateway);
		$method = $reflection->getMethod('get_api_base_url');

		$url = $method->invoke($this->gateway);
		$this->assertEquals('https://api-m.sandbox.paypal.com', $url);
	}

	/**
	 * Test API base URL in live mode.
	 */
	public function test_api_base_url_live(): void {

		$this->gateway->set_test_mode(false);

		$reflection = new \ReflectionClass($this->gateway);
		$method = $reflection->getMethod('get_api_base_url');

		$url = $method->invoke($this->gateway);
		$this->assertEquals('https://api-m.paypal.com', $url);
	}

	/**
	 * Test access token error without credentials.
	 */
	public function test_access_token_error_without_credentials(): void {

		$this->gateway->init();

		$reflection = new \ReflectionClass($this->gateway);
		$method = $reflection->getMethod('get_access_token');

		$result = $method->invoke($this->gateway);
		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('wu_paypal_missing_credentials', $result->get_error_code());
	}
}
