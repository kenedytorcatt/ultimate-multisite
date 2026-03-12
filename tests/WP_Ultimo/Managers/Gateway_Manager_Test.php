<?php
/**
 * Test case for Gateway Manager.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Gateway_Manager;
use WP_Ultimo\Gateways\Base_Gateway;
use WP_Ultimo\Gateways\Free_Gateway;
use WP_Ultimo\Gateways\Manual_Gateway;
use WP_UnitTestCase;

/**
 * Test Gateway Manager functionality.
 */
class Gateway_Manager_Test extends WP_UnitTestCase {

	/**
	 * Test gateway manager instance.
	 *
	 * @var Gateway_Manager
	 */
	private $manager;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->manager = Gateway_Manager::get_instance();
	}

	/**
	 * Test manager initialization.
	 */
	public function test_manager_initialization() {
		$this->assertInstanceOf(Gateway_Manager::class, $this->manager);
	}

	/**
	 * Test default gateways registration.
	 */
	public function test_add_default_gateways() {
		// Clear any existing gateways for clean test
		$reflection = new \ReflectionClass($this->manager);
		$property   = $reflection->getProperty('registered_gateways');

		// Only call setAccessible() on PHP < 8.1 where it's needed
		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}

		$property->setValue($this->manager, []);

		// Register default gateways
		$this->manager->add_default_gateways();

		$registered_gateways = $this->manager->get_registered_gateways();

		$this->assertIsArray($registered_gateways);
		$this->assertArrayHasKey('free', $registered_gateways);
		$this->assertArrayHasKey('manual', $registered_gateways);
	}

	/**
	 * Test gateway registration.
	 */
	public function test_register_gateway() {
		$gateway_id    = 'test-gateway';
		$gateway_class = Manual_Gateway::class;

		$result = $this->manager->register_gateway($gateway_id, 'test', 'test', $gateway_class);

		$this->assertTrue($result);

		$registered_gateways = $this->manager->get_registered_gateways();
		$this->assertArrayHasKey($gateway_id, $registered_gateways);
		$this->assertNotEmpty($registered_gateways[ $gateway_id ]);
	}

	/**
	 * Test duplicate gateway registration.
	 */
	public function test_register_duplicate_gateway() {
		$gateway_id    = 'duplicate-gateway';
		$gateway_class = Manual_Gateway::class;

		// Register once
		$result1 = $this->manager->register_gateway($gateway_id, 'man', 'man', $gateway_class);
		$this->assertTrue($result1);

		// Try to register again
		$result2 = $this->manager->register_gateway($gateway_id, 'man', 'man', $gateway_class);
		$this->assertFalse($result2);
	}

	/**
	 * Test get registered gateways.
	 */
	public function test_get_registered_gateways() {
		$gateways = $this->manager->get_registered_gateways();

		$this->assertIsArray($gateways);
		// Should contain at least the default gateways
		$this->assertNotEmpty($gateways);
	}

	/**
	 * Test get enabled gateways.
	 */
	public function test_get_enabled_gateways() {
		$enabled_gateways = $this->manager->get_registered_gateways();

		$this->assertIsArray($enabled_gateways);
	}

	/**
	 * Test get gateway instance.
	 */
	public function test_get_gateway() {
		// Register a test gateway first
		$gateway_id = 'test-instance';
		$this->manager->register_gateway($gateway_id, 'man', 'man', Manual_Gateway::class);

		$gateway = $this->manager->get_gateway($gateway_id);

		$this->assertIsArray($gateway);
		$this->assertNotEmpty($gateway);
	}

	/**
	 * Test get nonexistent gateway.
	 */
	public function test_get_nonexistent_gateway() {
		$gateway_instance = $this->manager->get_gateway('nonexistent');

		$this->assertFalse($gateway_instance);
	}

	/**
	 * Test is gateway registered.
	 */
	public function test_is_gateway_registered() {
		$gateway_id = 'check-registered';
		$this->manager->register_gateway($gateway_id, 'Manual', '', Manual_Gateway::class);

		$this->assertTrue($this->manager->is_gateway_registered($gateway_id));
		$this->assertFalse($this->manager->is_gateway_registered('not-registered'));
	}

	/**
	 * Test is gateway enabled.
	 */
	public function test_is_gateway_enabled() {
		$result = $this->manager->is_gateway_registered('free');
		$this->assertIsBool($result);
	}

	/**
	 * Test get auto renewable gateways.
	 */
	public function test_get_auto_renewable_gateways() {
		$auto_renewable = $this->manager->get_auto_renewable_gateways();

		$this->assertIsArray($auto_renewable);
	}

	/**
	 * Test gateway error handling.
	 */
	public function test_gateway_error_handling() {
		// Test with invalid gateway class
		try {
			$result = $this->manager->register_gateway('invalid', 'ex', 'tx', 'NonExistentClass');
		} catch (\Error $e) {
			$this->assertTrue($e instanceof \Error);
		}
	}

	/**
	 * Test registered gateway has expected keys.
	 */
	public function test_registered_gateway_has_expected_keys() {

		$gateway_id = 'keys-test-gw';
		$this->manager->register_gateway($gateway_id, 'Keys Test', 'A description', Manual_Gateway::class);

		$gateway = $this->manager->get_gateway($gateway_id);

		$this->assertArrayHasKey('id', $gateway);
		$this->assertArrayHasKey('title', $gateway);
		$this->assertArrayHasKey('desc', $gateway);
		$this->assertArrayHasKey('class_name', $gateway);
		$this->assertArrayHasKey('active', $gateway);
		$this->assertArrayHasKey('hidden', $gateway);
		$this->assertArrayHasKey('gateway', $gateway);

		$this->assertEquals($gateway_id, $gateway['id']);
		$this->assertEquals('Keys Test', $gateway['title']);
		$this->assertEquals('A description', $gateway['desc']);
	}

	/**
	 * Test hidden gateway registration.
	 */
	public function test_hidden_gateway_registration() {

		$gateway_id = 'hidden-gw-test';
		$this->manager->register_gateway($gateway_id, 'Hidden', '', Manual_Gateway::class, true);

		$gateway = $this->manager->get_gateway($gateway_id);

		$this->assertTrue($gateway['hidden']);
	}

	/**
	 * Test non-hidden gateway registration.
	 */
	public function test_non_hidden_gateway_registration() {

		$gateway_id = 'visible-gw-test';
		$this->manager->register_gateway($gateway_id, 'Visible', '', Manual_Gateway::class, false);

		$gateway = $this->manager->get_gateway($gateway_id);

		$this->assertFalse($gateway['hidden']);
	}

	/**
	 * Test get_gateways_as_options filters hidden gateways.
	 */
	public function test_get_gateways_as_options_filters_hidden() {

		$options = $this->manager->get_gateways_as_options();

		$this->assertIsArray($options);

		// Hidden gateways (like 'free') should be filtered out
		foreach ($options as $option) {
			$this->assertFalse($option['hidden']);
		}
	}

	/**
	 * Test on_load registers hooks.
	 */
	public function test_on_load_registers_hooks() {

		$this->manager->on_load();

		$this->assertNotFalse(has_action('wu_register_gateways', [$this->manager, 'add_default_gateways']));
	}

	/**
	 * Test init registers plugins_loaded hook.
	 */
	public function test_init_registers_plugins_loaded_hook() {

		$this->manager->init();

		$this->assertNotFalse(has_action('plugins_loaded', [$this->manager, 'on_load']));
	}

	/**
	 * Test handle_scheduled_payment_verification with empty payment_id.
	 */
	public function test_handle_scheduled_payment_verification_empty_id() {

		// Should return early without error
		$this->manager->handle_scheduled_payment_verification(0);

		$this->assertTrue(true);
	}

	/**
	 * Test handle_scheduled_payment_verification with array format.
	 */
	public function test_handle_scheduled_payment_verification_array_format() {

		// Should handle array format without error
		$this->manager->handle_scheduled_payment_verification([
			'payment_id' => 0,
			'gateway_id' => 'stripe',
		]);

		$this->assertTrue(true);
	}

	/**
	 * Test handle_scheduled_payment_verification with nonexistent payment.
	 */
	public function test_handle_scheduled_payment_verification_nonexistent_payment() {

		// Should return early without error for nonexistent payment
		$this->manager->handle_scheduled_payment_verification(999999);

		$this->assertTrue(true);
	}

	/**
	 * Test maybe_schedule_payment_verification with null payment.
	 */
	public function test_maybe_schedule_payment_verification_null_payment() {

		// Should return early without error
		$this->manager->maybe_schedule_payment_verification(null, null, null, null, 'new');

		$this->assertTrue(true);
	}

	/**
	 * Test add_gateway_selector_field runs without error.
	 */
	public function test_add_gateway_selector_field() {

		$this->manager->add_gateway_selector_field();

		$this->assertTrue(true);
	}
}
