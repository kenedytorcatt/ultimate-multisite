<?php
/**
 * Tests for Signup_Field_Color class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Color.
 */
class Signup_Field_Color_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Color
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Color();
	}

	/**
	 * Test get_type returns color_picker.
	 */
	public function test_get_type(): void {
		$this->assertEquals('color_picker', $this->field->get_type());
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
		$this->assertEquals('Color', $title);
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
	 * Test defaults returns array.
	 */
	public function test_defaults(): void {
		$defaults = $this->field->defaults();
		$this->assertIsArray($defaults);
	}

	/**
	 * Test default_fields contains expected fields.
	 */
	public function test_default_fields(): void {
		$fields = $this->field->default_fields();
		$this->assertIsArray($fields);
		$this->assertContains('id', $fields);
		$this->assertContains('name', $fields);
		$this->assertContains('required', $fields);
	}

	/**
	 * Test get_fields returns array with default_value.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
		$this->assertArrayHasKey('default_value', $fields);
	}

	/**
	 * Test to_fields_array returns proper structure.
	 */
	public function test_to_fields_array(): void {
		$attributes = [
			'id'              => 'brand_color',
			'name'            => 'Brand Color',
			'placeholder'     => '#ffffff',
			'tooltip'         => 'Pick your brand color',
			'default_value'   => '#0000ff',
			'required'        => false,
			'element_classes' => 'color-class',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('brand_color', $fields);
		$this->assertEquals('color', $fields['brand_color']['type']);
		$this->assertEquals('Brand Color', $fields['brand_color']['name']);
		$this->assertEquals('#0000ff', $fields['brand_color']['default']);
	}
}
