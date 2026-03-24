<?php
/**
 * Tests for PayPal OAuth Handler.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 * @since 2.x.x
 */

namespace WP_Ultimo\Gateways;

use WP_UnitTestCase;

/**
 * PayPal OAuth Handler Test class.
 */
class PayPal_OAuth_Handler_Test extends WP_UnitTestCase {

	/**
	 * Test handler instance.
	 *
	 * @var PayPal_OAuth_Handler
	 */
	protected $handler;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Clear all PayPal REST settings
		wu_save_setting('paypal_rest_sandbox_mode', 1);
		wu_save_setting('paypal_rest_sandbox_merchant_id', '');
		wu_save_setting('paypal_rest_sandbox_merchant_email', '');
		wu_save_setting('paypal_rest_live_merchant_id', '');
		wu_save_setting('paypal_rest_live_merchant_email', '');
		wu_save_setting('paypal_rest_connection_date', '');

		$this->handler = PayPal_OAuth_Handler::get_instance();
	}

	/**
	 * Test handler is singleton.
	 */
	public function test_singleton(): void {

		$instance1 = PayPal_OAuth_Handler::get_instance();
		$instance2 = PayPal_OAuth_Handler::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test is_configured returns true (proxy URL is always set).
	 */
	public function test_is_configured(): void {

		$this->assertTrue($this->handler->is_configured());
	}

	/**
	 * Test is_configured returns false when proxy URL is empty.
	 */
	public function test_is_configured_false_without_proxy(): void {

		add_filter('wu_paypal_connect_proxy_url', '__return_empty_string');

		$this->assertFalse($this->handler->is_configured());

		remove_filter('wu_paypal_connect_proxy_url', '__return_empty_string');
	}

	/**
	 * Test merchant not connected without merchant ID.
	 */
	public function test_merchant_not_connected_without_id(): void {

		$this->assertFalse($this->handler->is_merchant_connected(true));
		$this->assertFalse($this->handler->is_merchant_connected(false));
	}

	/**
	 * Test merchant connected in sandbox mode.
	 */
	public function test_merchant_connected_sandbox(): void {

		wu_save_setting('paypal_rest_sandbox_merchant_id', 'SANDBOX_MERCHANT_123');

		$this->assertTrue($this->handler->is_merchant_connected(true));
		$this->assertFalse($this->handler->is_merchant_connected(false));
	}

	/**
	 * Test merchant connected in live mode.
	 */
	public function test_merchant_connected_live(): void {

		wu_save_setting('paypal_rest_live_merchant_id', 'LIVE_MERCHANT_456');

		$this->assertFalse($this->handler->is_merchant_connected(true));
		$this->assertTrue($this->handler->is_merchant_connected(false));
	}

	/**
	 * Test get_merchant_details returns correct sandbox data.
	 */
	public function test_get_merchant_details_sandbox(): void {

		wu_save_setting('paypal_rest_sandbox_merchant_id', 'MERCHANT_ID_TEST');
		wu_save_setting('paypal_rest_sandbox_merchant_email', 'test@merchant.com');
		wu_save_setting('paypal_rest_sandbox_payments_receivable', true);
		wu_save_setting('paypal_rest_sandbox_email_confirmed', true);
		wu_save_setting('paypal_rest_connection_date', '2026-02-01 10:00:00');

		$details = $this->handler->get_merchant_details(true);

		$this->assertEquals('MERCHANT_ID_TEST', $details['merchant_id']);
		$this->assertEquals('test@merchant.com', $details['merchant_email']);
		$this->assertTrue($details['payments_receivable']);
		$this->assertTrue($details['email_confirmed']);
		$this->assertEquals('2026-02-01 10:00:00', $details['connection_date']);
	}

	/**
	 * Test get_merchant_details returns correct live data.
	 */
	public function test_get_merchant_details_live(): void {

		wu_save_setting('paypal_rest_live_merchant_id', 'LIVE_MID');
		wu_save_setting('paypal_rest_live_merchant_email', 'live@merchant.com');

		$details = $this->handler->get_merchant_details(false);

		$this->assertEquals('LIVE_MID', $details['merchant_id']);
		$this->assertEquals('live@merchant.com', $details['merchant_email']);
	}

	/**
	 * Test get_merchant_details returns empty defaults when not connected.
	 */
	public function test_get_merchant_details_empty(): void {

		$details = $this->handler->get_merchant_details(true);

		$this->assertEmpty($details['merchant_id']);
		$this->assertEmpty($details['merchant_email']);
		$this->assertEmpty($details['connection_date']);
	}

	/**
	 * Test init registers AJAX hooks.
	 */
	public function test_init_registers_hooks(): void {

		$this->handler->init();

		$this->assertNotFalse(has_action('wp_ajax_wu_paypal_connect', [$this->handler, 'ajax_initiate_oauth']));
		$this->assertNotFalse(has_action('wp_ajax_wu_paypal_disconnect', [$this->handler, 'ajax_disconnect']));
		$this->assertNotFalse(has_action('admin_init', [$this->handler, 'handle_oauth_return']));
	}

	/**
	 * Test is_oauth_feature_enabled defaults to false (proxy unreachable in tests).
	 */
	public function test_oauth_feature_disabled_by_default(): void {

		// Clear any cached transient
		delete_site_transient('wu_paypal_oauth_enabled');

		// In test environment the proxy is unreachable, so it should be false
		// We use a filter override to avoid the actual HTTP call
		add_filter('wu_paypal_oauth_enabled', '__return_false');

		$this->assertFalse($this->handler->is_oauth_feature_enabled());

		remove_filter('wu_paypal_oauth_enabled', '__return_false');
	}

	/**
	 * Test is_oauth_feature_enabled returns true with filter override.
	 */
	public function test_oauth_feature_enabled_via_filter(): void {

		add_filter('wu_paypal_oauth_enabled', '__return_true');

		$this->assertTrue($this->handler->is_oauth_feature_enabled());

		remove_filter('wu_paypal_oauth_enabled', '__return_true');
	}

	/**
	 * Test is_oauth_feature_enabled respects cached transient.
	 */
	public function test_oauth_feature_uses_transient_cache(): void {

		// Set the transient directly
		set_site_transient('wu_paypal_oauth_enabled', 'yes', HOUR_IN_SECONDS);

		$this->assertTrue($this->handler->is_oauth_feature_enabled());

		set_site_transient('wu_paypal_oauth_enabled', 'no', HOUR_IN_SECONDS);

		$this->assertFalse($this->handler->is_oauth_feature_enabled());

		// Cleanup
		delete_site_transient('wu_paypal_oauth_enabled');
	}
}
