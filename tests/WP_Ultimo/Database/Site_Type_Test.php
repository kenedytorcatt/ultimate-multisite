<?php
/**
 * Tests for Site_Type enum.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Database;

use WP_UnitTestCase;
use WP_Ultimo\Database\Sites\Site_Type;

/**
 * Test class for Site_Type enum.
 */
class Site_Type_Test extends WP_UnitTestCase {

	/**
	 * Test type constants are defined.
	 */
	public function test_type_constants_defined(): void {
		$this->assertEquals('default', Site_Type::REGULAR);
		$this->assertEquals('site_template', Site_Type::SITE_TEMPLATE);
		$this->assertEquals('customer_owned', Site_Type::CUSTOMER_OWNED);
		$this->assertEquals('pending', Site_Type::PENDING);
		$this->assertEquals('external', Site_Type::EXTERNAL);
		$this->assertEquals('main', Site_Type::MAIN);
	}

	/**
	 * Test default value is default.
	 */
	public function test_default_value(): void {
		$type = new Site_Type();
		$this->assertEquals('default', $type->get_value());
	}

	/**
	 * Test get_value with valid type.
	 */
	public function test_get_value_valid(): void {
		$type = new Site_Type(Site_Type::SITE_TEMPLATE);
		$this->assertEquals('site_template', $type->get_value());
	}

	/**
	 * Test get_value with invalid type returns default.
	 */
	public function test_get_value_invalid_returns_default(): void {
		$type = new Site_Type('invalid_type');
		$this->assertEquals('default', $type->get_value());
	}

	/**
	 * Test is_valid with valid types.
	 */
	public function test_is_valid_true(): void {
		$type = new Site_Type();
		$this->assertTrue($type->is_valid(Site_Type::REGULAR));
		$this->assertTrue($type->is_valid(Site_Type::SITE_TEMPLATE));
		$this->assertTrue($type->is_valid(Site_Type::CUSTOMER_OWNED));
		$this->assertTrue($type->is_valid(Site_Type::PENDING));
		$this->assertTrue($type->is_valid(Site_Type::EXTERNAL));
		$this->assertTrue($type->is_valid(Site_Type::MAIN));
	}

	/**
	 * Test is_valid with invalid types.
	 */
	public function test_is_valid_false(): void {
		$type = new Site_Type();
		$this->assertFalse($type->is_valid('invalid'));
		$this->assertFalse($type->is_valid(''));
		$this->assertFalse($type->is_valid('DEFAULT')); // Case sensitive
	}

	/**
	 * Test get_label returns correct label.
	 */
	public function test_get_label(): void {
		$regular = new Site_Type(Site_Type::REGULAR);
		$this->assertEquals('Regular Site', $regular->get_label());

		$template = new Site_Type(Site_Type::SITE_TEMPLATE);
		$this->assertEquals('Site Template', $template->get_label());

		$customer = new Site_Type(Site_Type::CUSTOMER_OWNED);
		$this->assertEquals('Customer-Owned', $customer->get_label());
	}

	/**
	 * Test get_classes returns CSS classes.
	 */
	public function test_get_classes(): void {
		$regular = new Site_Type(Site_Type::REGULAR);
		$this->assertStringContainsString('wu-bg-gray-700', $regular->get_classes());

		$template = new Site_Type(Site_Type::SITE_TEMPLATE);
		$this->assertStringContainsString('wu-bg-yellow-200', $template->get_classes());

		$customer = new Site_Type(Site_Type::CUSTOMER_OWNED);
		$this->assertStringContainsString('wu-bg-green-200', $customer->get_classes());
	}

	/**
	 * Test get_options returns all type options.
	 */
	public function test_get_options(): void {
		$options = Site_Type::get_options();

		$this->assertIsArray($options);
		$this->assertContains(Site_Type::REGULAR, $options);
		$this->assertContains(Site_Type::SITE_TEMPLATE, $options);
		$this->assertContains(Site_Type::CUSTOMER_OWNED, $options);
	}

	/**
	 * Test get_allowed_list returns array.
	 */
	public function test_get_allowed_list_array(): void {
		$list = Site_Type::get_allowed_list();

		$this->assertIsArray($list);
		$this->assertContains(Site_Type::REGULAR, $list);
	}

	/**
	 * Test get_allowed_list returns string.
	 */
	public function test_get_allowed_list_string(): void {
		$list = Site_Type::get_allowed_list(true);

		$this->assertIsString($list);
		$this->assertStringContainsString('default', $list);
		$this->assertStringContainsString('site_template', $list);
	}

	/**
	 * Test to_array returns labels.
	 */
	public function test_to_array(): void {
		$labels = Site_Type::to_array();

		$this->assertIsArray($labels);
		$this->assertNotEmpty($labels);
		// Check that we have at least one label
		$this->assertGreaterThan(0, count($labels));
	}

	/**
	 * Test __toString returns value.
	 */
	public function test_to_string(): void {
		$type = new Site_Type(Site_Type::SITE_TEMPLATE);
		$this->assertEquals('site_template', (string) $type);
	}

	/**
	 * Test static call returns constant value.
	 */
	public function test_static_call(): void {
		$this->assertEquals('default', Site_Type::REGULAR());
		$this->assertEquals('site_template', Site_Type::SITE_TEMPLATE());
	}
}
