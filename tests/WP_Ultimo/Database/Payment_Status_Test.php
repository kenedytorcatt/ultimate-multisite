<?php
/**
 * Tests for Payment_Status enum.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Database;

use WP_UnitTestCase;
use WP_Ultimo\Database\Payments\Payment_Status;

/**
 * Test class for Payment_Status enum.
 */
class Payment_Status_Test extends WP_UnitTestCase {

	/**
	 * Test status constants are defined.
	 */
	public function test_status_constants_defined(): void {
		$this->assertEquals('pending', Payment_Status::PENDING);
		$this->assertEquals('completed', Payment_Status::COMPLETED);
		$this->assertEquals('refunded', Payment_Status::REFUND);
		$this->assertEquals('partially-refunded', Payment_Status::PARTIAL_REFUND);
		$this->assertEquals('partially-paid', Payment_Status::PARTIAL);
		$this->assertEquals('failed', Payment_Status::FAILED);
		$this->assertEquals('cancelled', Payment_Status::CANCELLED);
		$this->assertEquals('draft', Payment_Status::DRAFT);
	}

	/**
	 * Test default value is pending.
	 */
	public function test_default_value(): void {
		$status = new Payment_Status();
		$this->assertEquals('pending', $status->get_value());
	}

	/**
	 * Test get_value with valid status.
	 */
	public function test_get_value_valid(): void {
		$status = new Payment_Status(Payment_Status::COMPLETED);
		$this->assertEquals('completed', $status->get_value());
	}

	/**
	 * Test get_value with invalid status returns default.
	 */
	public function test_get_value_invalid_returns_default(): void {
		$status = new Payment_Status('invalid_status');
		$this->assertEquals('pending', $status->get_value());
	}

	/**
	 * Test is_valid with valid status.
	 */
	public function test_is_valid_true(): void {
		$status = new Payment_Status();
		$this->assertTrue($status->is_valid(Payment_Status::PENDING));
		$this->assertTrue($status->is_valid(Payment_Status::COMPLETED));
		$this->assertTrue($status->is_valid(Payment_Status::REFUND));
	}

	/**
	 * Test is_valid with invalid status.
	 */
	public function test_is_valid_false(): void {
		$status = new Payment_Status();
		$this->assertFalse($status->is_valid('invalid'));
		$this->assertFalse($status->is_valid(''));
		$this->assertFalse($status->is_valid('PENDING')); // Case sensitive
	}

	/**
	 * Test get_label returns correct label.
	 */
	public function test_get_label(): void {
		$pending = new Payment_Status(Payment_Status::PENDING);
		$this->assertEquals('Pending', $pending->get_label());

		$completed = new Payment_Status(Payment_Status::COMPLETED);
		$this->assertEquals('Completed', $completed->get_label());

		$refunded = new Payment_Status(Payment_Status::REFUND);
		$this->assertEquals('Refunded', $refunded->get_label());
	}

	/**
	 * Test get_classes returns CSS classes.
	 */
	public function test_get_classes(): void {
		$pending = new Payment_Status(Payment_Status::PENDING);
		$this->assertStringContainsString('wu-bg-gray-200', $pending->get_classes());

		$completed = new Payment_Status(Payment_Status::COMPLETED);
		$this->assertStringContainsString('wu-bg-green-200', $completed->get_classes());

		$failed = new Payment_Status(Payment_Status::FAILED);
		$this->assertStringContainsString('wu-bg-red-200', $failed->get_classes());
	}

	/**
	 * Test get_icon_classes returns icon classes.
	 */
	public function test_get_icon_classes(): void {
		$pending = new Payment_Status(Payment_Status::PENDING);
		$this->assertStringContainsString('dashicons-wu-clock', $pending->get_icon_classes());

		$completed = new Payment_Status(Payment_Status::COMPLETED);
		$this->assertStringContainsString('dashicons-wu-check', $completed->get_icon_classes());
	}

	/**
	 * Test get_options returns all status options.
	 */
	public function test_get_options(): void {
		$options = Payment_Status::get_options();

		$this->assertIsArray($options);
		$this->assertContains(Payment_Status::PENDING, $options);
		$this->assertContains(Payment_Status::COMPLETED, $options);
		$this->assertContains(Payment_Status::FAILED, $options);
	}

	/**
	 * Test get_allowed_list returns array.
	 */
	public function test_get_allowed_list_array(): void {
		$list = Payment_Status::get_allowed_list();

		$this->assertIsArray($list);
		$this->assertContains(Payment_Status::PENDING, $list);
	}

	/**
	 * Test get_allowed_list returns string.
	 */
	public function test_get_allowed_list_string(): void {
		$list = Payment_Status::get_allowed_list(true);

		$this->assertIsString($list);
		$this->assertStringContainsString('pending', $list);
		$this->assertStringContainsString('completed', $list);
	}

	/**
	 * Test to_array returns labels.
	 */
	public function test_to_array(): void {
		$labels = Payment_Status::to_array();

		$this->assertIsArray($labels);
		$this->assertArrayHasKey(Payment_Status::PENDING, $labels);
		// to_array returns labels array which has status values as keys
		$this->assertNotEmpty($labels);
		$this->assertEquals('Pending', $labels[Payment_Status::PENDING]);
	}

	/**
	 * Test __toString returns value.
	 */
	public function test_to_string(): void {
		$status = new Payment_Status(Payment_Status::COMPLETED);
		$this->assertEquals('completed', (string) $status);
	}

	/**
	 * Test static call returns constant value.
	 */
	public function test_static_call(): void {
		$this->assertEquals('pending', Payment_Status::PENDING());
		$this->assertEquals('completed', Payment_Status::COMPLETED());
	}

	/**
	 * Test get_hook_name returns correct hook.
	 */
	public function test_get_hook_name(): void {
		$hook = Payment_Status::get_hook_name();
		$this->assertEquals('payment_status', $hook);
	}
}
