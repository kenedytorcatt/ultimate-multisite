<?php
/**
 * Tests for API class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

use WP_UnitTestCase;

/**
 * Test class for API.
 */
class API_Test extends WP_UnitTestCase {

	/**
	 * @var API
	 */
	private API $api;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {

		parent::set_up();
		$this->api = API::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(API::class, $this->api);
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(API::get_instance(), API::get_instance());
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$this->api->init();

		$this->assertGreaterThan(0, has_action('init', [$this->api, 'add_settings']));
		$this->assertGreaterThan(0, has_action('rest_api_init', [$this->api, 'register_routes']));
		$this->assertGreaterThan(0, has_filter('rest_request_after_callbacks', [$this->api, 'log_api_errors']));
		$this->assertGreaterThan(0, has_filter('rest_authentication_errors', [$this->api, 'maybe_bypass_wp_auth']));
	}

	/**
	 * Test maybe_bypass_wp_auth with true returns true.
	 */
	public function test_maybe_bypass_wp_auth_already_bypassed(): void {

		$result = $this->api->maybe_bypass_wp_auth(true);

		$this->assertTrue($result);
	}

	/**
	 * Test maybe_bypass_wp_auth with null (no other handler).
	 */
	public function test_maybe_bypass_wp_auth_null(): void {

		$result = $this->api->maybe_bypass_wp_auth(null);

		// Should return null or a value (depends on request URI).
		$this->assertNotTrue($result);
	}

	/**
	 * Test maybe_bypass_wp_auth with WP_Error.
	 */
	public function test_maybe_bypass_wp_auth_wp_error(): void {

		$error  = new \WP_Error('test', 'test error');
		$result = $this->api->maybe_bypass_wp_auth($error);

		// Should pass through or handle the error.
		$this->assertNotTrue($result);
	}
}
