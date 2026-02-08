<?php
/**
 * Tests for Product_Type enum.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Database;

use WP_UnitTestCase;
use WP_Ultimo\Database\Products\Product_Type;

/**
 * Test class for Product_Type enum.
 */
class Product_Type_Test extends WP_UnitTestCase {

	/**
	 * Test type constants are defined.
	 */
	public function test_type_constants_defined(): void {
		$this->assertEquals('plan', Product_Type::PLAN);
		$this->assertEquals('package', Product_Type::PACKAGE);
		$this->assertEquals('service', Product_Type::SERVICE);
	}

	/**
	 * Test default value is plan.
	 */
	public function test_default_value(): void {
		$type = new Product_Type();
		$this->assertEquals('plan', $type->get_value());
	}

	/**
	 * Test get_value with valid type.
	 */
	public function test_get_value_valid(): void {
		$type = new Product_Type(Product_Type::PACKAGE);
		$this->assertEquals('package', $type->get_value());
	}

	/**
	 * Test get_value with invalid type returns default.
	 */
	public function test_get_value_invalid_returns_default(): void {
		$type = new Product_Type('invalid_type');
		$this->assertEquals('plan', $type->get_value());
	}

	/**
	 * Test is_valid with valid types.
	 */
	public function test_is_valid_true(): void {
		$type = new Product_Type();
		$this->assertTrue($type->is_valid(Product_Type::PLAN));
		$this->assertTrue($type->is_valid(Product_Type::PACKAGE));
		$this->assertTrue($type->is_valid(Product_Type::SERVICE));
	}

	/**
	 * Test is_valid with invalid types.
	 */
	public function test_is_valid_false(): void {
		$type = new Product_Type();
		$this->assertFalse($type->is_valid('invalid'));
		$this->assertFalse($type->is_valid(''));
		$this->assertFalse($type->is_valid('PLAN')); // Case sensitive
	}

	/**
	 * Test get_label returns correct label.
	 */
	public function test_get_label(): void {
		$plan = new Product_Type(Product_Type::PLAN);
		$this->assertEquals('Plan', $plan->get_label());

		$package = new Product_Type(Product_Type::PACKAGE);
		$this->assertEquals('Package', $package->get_label());

		$service = new Product_Type(Product_Type::SERVICE);
		$this->assertEquals('Service', $service->get_label());
	}

	/**
	 * Test get_classes returns CSS classes.
	 */
	public function test_get_classes(): void {
		$plan = new Product_Type(Product_Type::PLAN);
		$this->assertStringContainsString('wu-bg-green-200', $plan->get_classes());

		$package = new Product_Type(Product_Type::PACKAGE);
		$this->assertStringContainsString('wu-bg-gray-200', $package->get_classes());

		$service = new Product_Type(Product_Type::SERVICE);
		$this->assertStringContainsString('wu-bg-yellow-200', $service->get_classes());
	}

	/**
	 * Test get_options returns all type options.
	 */
	public function test_get_options(): void {
		$options = Product_Type::get_options();

		$this->assertIsArray($options);
		$this->assertContains(Product_Type::PLAN, $options);
		$this->assertContains(Product_Type::PACKAGE, $options);
		$this->assertContains(Product_Type::SERVICE, $options);
	}

	/**
	 * Test get_allowed_list returns array.
	 */
	public function test_get_allowed_list_array(): void {
		$list = Product_Type::get_allowed_list();

		$this->assertIsArray($list);
		$this->assertContains(Product_Type::PLAN, $list);
	}

	/**
	 * Test get_allowed_list returns string.
	 */
	public function test_get_allowed_list_string(): void {
		$list = Product_Type::get_allowed_list(true);

		$this->assertIsString($list);
		$this->assertStringContainsString('plan', $list);
		$this->assertStringContainsString('package', $list);
	}

	/**
	 * Test to_array returns labels.
	 */
	public function test_to_array(): void {
		$labels = Product_Type::to_array();

		$this->assertIsArray($labels);
		$this->assertNotEmpty($labels);
		// Check that we have at least one label
		$this->assertGreaterThan(0, count($labels));
	}

	/**
	 * Test __toString returns value.
	 */
	public function test_to_string(): void {
		$type = new Product_Type(Product_Type::PACKAGE);
		$this->assertEquals('package', (string) $type);
	}

	/**
	 * Test static call returns constant value.
	 */
	public function test_static_call(): void {
		$this->assertEquals('plan', Product_Type::PLAN());
		$this->assertEquals('package', Product_Type::PACKAGE());
	}
}
