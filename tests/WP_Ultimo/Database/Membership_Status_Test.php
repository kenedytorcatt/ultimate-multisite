<?php
/**
 * Tests for Membership_Status enum.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Database;

use WP_UnitTestCase;
use WP_Ultimo\Database\Memberships\Membership_Status;

/**
 * Test class for Membership_Status enum.
 */
class Membership_Status_Test extends WP_UnitTestCase {

	/**
	 * Test status constants are defined.
	 */
	public function test_status_constants_defined(): void {
		$this->assertEquals('pending', Membership_Status::PENDING);
		$this->assertEquals('active', Membership_Status::ACTIVE);
		$this->assertEquals('trialing', Membership_Status::TRIALING);
		$this->assertEquals('expired', Membership_Status::EXPIRED);
		$this->assertEquals('on-hold', Membership_Status::ON_HOLD);
		$this->assertEquals('cancelled', Membership_Status::CANCELLED);
	}

	/**
	 * Test default value is pending.
	 */
	public function test_default_value(): void {
		$status = new Membership_Status();
		$this->assertEquals('pending', $status->get_value());
	}

	/**
	 * Test get_value with valid status.
	 */
	public function test_get_value_valid(): void {
		$status = new Membership_Status(Membership_Status::ACTIVE);
		$this->assertEquals('active', $status->get_value());
	}

	/**
	 * Test get_value with invalid status returns default.
	 */
	public function test_get_value_invalid_returns_default(): void {
		$status = new Membership_Status('invalid_status');
		$this->assertEquals('pending', $status->get_value());
	}

	/**
	 * Test is_valid with valid status.
	 */
	public function test_is_valid_true(): void {
		$status = new Membership_Status();
		$this->assertTrue($status->is_valid(Membership_Status::PENDING));
		$this->assertTrue($status->is_valid(Membership_Status::ACTIVE));
		$this->assertTrue($status->is_valid(Membership_Status::TRIALING));
		$this->assertTrue($status->is_valid(Membership_Status::EXPIRED));
		$this->assertTrue($status->is_valid(Membership_Status::ON_HOLD));
		$this->assertTrue($status->is_valid(Membership_Status::CANCELLED));
	}

	/**
	 * Test is_valid with invalid status.
	 */
	public function test_is_valid_false(): void {
		$status = new Membership_Status();
		$this->assertFalse($status->is_valid('invalid'));
		$this->assertFalse($status->is_valid(''));
		$this->assertFalse($status->is_valid('ACTIVE')); // Case sensitive
	}

	/**
	 * Test get_label returns correct label.
	 */
	public function test_get_label(): void {
		$pending = new Membership_Status(Membership_Status::PENDING);
		$this->assertEquals('Pending', $pending->get_label());

		$active = new Membership_Status(Membership_Status::ACTIVE);
		$this->assertEquals('Active', $active->get_label());

		$trialing = new Membership_Status(Membership_Status::TRIALING);
		$this->assertEquals('Trialing', $trialing->get_label());

		$onhold = new Membership_Status(Membership_Status::ON_HOLD);
		$this->assertEquals('On Hold', $onhold->get_label());
	}

	/**
	 * Test get_classes returns CSS classes.
	 */
	public function test_get_classes(): void {
		$pending = new Membership_Status(Membership_Status::PENDING);
		$this->assertStringContainsString('wu-bg-gray-200', $pending->get_classes());

		$active = new Membership_Status(Membership_Status::ACTIVE);
		$this->assertStringContainsString('wu-bg-green-200', $active->get_classes());

		$cancelled = new Membership_Status(Membership_Status::CANCELLED);
		$this->assertStringContainsString('wu-bg-red-200', $cancelled->get_classes());
	}

	/**
	 * Test get_options returns all status options.
	 */
	public function test_get_options(): void {
		$options = Membership_Status::get_options();

		$this->assertIsArray($options);
		$this->assertContains(Membership_Status::PENDING, $options);
		$this->assertContains(Membership_Status::ACTIVE, $options);
		$this->assertContains(Membership_Status::TRIALING, $options);
	}

	/**
	 * Test get_allowed_list returns array.
	 */
	public function test_get_allowed_list_array(): void {
		$list = Membership_Status::get_allowed_list();

		$this->assertIsArray($list);
		$this->assertContains(Membership_Status::ACTIVE, $list);
	}

	/**
	 * Test get_allowed_list returns string.
	 */
	public function test_get_allowed_list_string(): void {
		$list = Membership_Status::get_allowed_list(true);

		$this->assertIsString($list);
		$this->assertStringContainsString('active', $list);
		$this->assertStringContainsString('pending', $list);
	}

	/**
	 * Test to_array returns labels.
	 */
	public function test_to_array(): void {
		$labels = Membership_Status::to_array();

		$this->assertIsArray($labels);
		$this->assertArrayHasKey(Membership_Status::PENDING, $labels);
		$this->assertArrayHasKey(Membership_Status::ACTIVE, $labels);
		$this->assertEquals('Active', $labels[Membership_Status::ACTIVE]);
	}

	/**
	 * Test __toString returns value.
	 */
	public function test_to_string(): void {
		$status = new Membership_Status(Membership_Status::ACTIVE);
		$this->assertEquals('active', (string) $status);
	}

	/**
	 * Test static call returns constant value.
	 */
	public function test_static_call(): void {
		$this->assertEquals('pending', Membership_Status::PENDING());
		$this->assertEquals('active', Membership_Status::ACTIVE());
	}

	/**
	 * Test get_hook_name returns correct hook.
	 */
	public function test_get_hook_name(): void {
		$hook = Membership_Status::get_hook_name();
		$this->assertEquals('membership_status', $hook);
	}
}
