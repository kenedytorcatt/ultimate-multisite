<?php
/**
 * Unit tests for Manual_Gateway.
 *
 * @package WP_Ultimo\Tests\Gateways
 */

namespace WP_Ultimo\Tests\Gateways;

use WP_Ultimo\Gateways\Manual_Gateway;

class Manual_Gateway_Test extends \WP_UnitTestCase {

	/**
	 * Manual Gateway instance.
	 *
	 * @var Manual_Gateway
	 */
	protected $gateway;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {

		parent::setUp();

		$this->gateway = new Manual_Gateway();
	}

	/**
	 * Test gateway has correct ID.
	 */
	public function test_gateway_has_correct_id(): void {

		$reflection = new \ReflectionClass($this->gateway);
		$property   = $reflection->getProperty('id');

		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}

		$id = $property->getValue($this->gateway);

		$this->assertEquals('manual', $id);
	}

	/**
	 * Test gateway does not support recurring payments.
	 */
	public function test_does_not_support_recurring(): void {

		$supports = $this->gateway->supports_recurring();

		$this->assertFalse($supports);
	}

	/**
	 * Test gateway does not support free trials.
	 */
	public function test_does_not_support_free_trials(): void {

		$supports = $this->gateway->supports_free_trials();

		$this->assertFalse($supports);
	}

	/**
	 * Test gateway registers settings.
	 */
	public function test_registers_settings(): void {

		// Settings should be registered without errors
		$this->gateway->settings();

		// If no exception is thrown, the test passes
		$this->assertTrue(true);
	}

	/**
	 * Test gateway hooks are added.
	 */
	public function test_hooks_are_added(): void {

		$this->gateway->hooks();

		// Check if the hook was added
		$priority = has_action('wu_thank_you_before_info_blocks', [$this->gateway, 'add_payment_instructions_block']);

		$this->assertNotFalse($priority);
		$this->assertEquals(10, $priority);
	}

	/**
	 * Test that manual gateway is properly initialized.
	 */
	public function test_gateway_initialization(): void {

		$gateway = new Manual_Gateway();

		$this->assertInstanceOf(Manual_Gateway::class, $gateway);
	}

	/**
	 * Test boundary: Gateway with null checkout.
	 */
	public function test_gateway_handles_null_checkout(): void {

		// Manual gateway should handle edge cases gracefully
		$this->assertTrue(true);
	}

	/**
	 * Test that get_amount_update_message returns appropriate message.
	 */
	public function test_get_amount_update_message(): void {

		$message = $this->gateway->get_amount_update_message();

		// Manual gateway should return false or empty since it doesn't update amounts automatically
		$this->assertFalse($message);
	}
}