<?php
/**
 * Tests for Signup_Field_Discount_Code class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Discount_Code.
 */
class Signup_Field_Discount_Code_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Discount_Code
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Discount_Code();
	}

	/**
	 * Test get_type returns discount_code.
	 */
	public function test_get_type(): void {
		$this->assertEquals('discount_code', $this->field->get_type());
	}

	/**
	 * Test is_required returns false.
	 */
	public function test_is_required(): void {
		$this->assertFalse($this->field->is_required());
	}

	/**
	 * Test get_title returns string.
	 */
	public function test_get_title(): void {
		$title = $this->field->get_title();
		$this->assertIsString($title);
		$this->assertEquals('Coupon Code', $title);
	}

	/**
	 * Test get_description returns string.
	 */
	public function test_get_description(): void {
		$description = $this->field->get_description();
		$this->assertIsString($description);
		$this->assertNotEmpty($description);
	}

	/**
	 * Test get_tooltip returns string.
	 */
	public function test_get_tooltip(): void {
		$tooltip = $this->field->get_tooltip();
		$this->assertIsString($tooltip);
		$this->assertNotEmpty($tooltip);
	}

	/**
	 * Test get_icon returns dashicon class.
	 */
	public function test_get_icon(): void {
		$icon = $this->field->get_icon();
		$this->assertIsString($icon);
		$this->assertStringContainsString('dashicons', $icon);
	}

	/**
	 * Test is_user_field returns false.
	 */
	public function test_is_user_field(): void {
		$this->assertFalse($this->field->is_user_field());
	}

	/**
	 * Test is_site_field returns false.
	 */
	public function test_is_site_field(): void {
		$this->assertFalse($this->field->is_site_field());
	}

	/**
	 * Test defaults returns array with expected keys.
	 */
	public function test_defaults(): void {
		$defaults = $this->field->defaults();
		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('placeholder', $defaults);
		$this->assertArrayHasKey('default', $defaults);
	}

	/**
	 * Test default_fields contains expected fields.
	 */
	public function test_default_fields(): void {
		$fields = $this->field->default_fields();
		$this->assertIsArray($fields);
		$this->assertContains('name', $fields);
		$this->assertContains('placeholder', $fields);
		$this->assertContains('tooltip', $fields);
	}

	/**
	 * Test force_attributes returns expected values.
	 */
	public function test_force_attributes(): void {
		$forced = $this->field->force_attributes();
		$this->assertIsArray($forced);
		$this->assertArrayHasKey('id', $forced);
		$this->assertEquals('discount_code', $forced['id']);
	}

	/**
	 * Test get_fields returns empty array.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
		$this->assertEmpty($fields);
	}

	/**
	 * Test to_fields_array returns proper structure.
	 */
	public function test_to_fields_array(): void {
		$attributes = [
			'id'          => 'discount_code',
			'name'        => 'Coupon Code',
			'placeholder' => 'Enter coupon',
			'tooltip'     => 'Enter your discount code',
			'default'     => '',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('discount_code_checkbox', $fields);
		$this->assertArrayHasKey('discount_code', $fields);
		$this->assertEquals('toggle', $fields['discount_code_checkbox']['type']);
		$this->assertEquals('text', $fields['discount_code']['type']);
	}
}
