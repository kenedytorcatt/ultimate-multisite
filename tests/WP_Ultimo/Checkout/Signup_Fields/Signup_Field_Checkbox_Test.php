<?php
/**
 * Tests for Signup_Field_Checkbox class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Checkbox.
 */
class Signup_Field_Checkbox_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Checkbox
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Checkbox();
	}

	/**
	 * Test get_type returns checkbox.
	 */
	public function test_get_type(): void {
		$this->assertEquals('checkbox', $this->field->get_type());
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
		$this->assertEquals('Checkbox', $title);
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
		$this->assertContains('tooltip', $fields);
		$this->assertContains('required', $fields);
	}

	/**
	 * Test get_fields returns array with expected keys.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
		$this->assertArrayHasKey('default_state', $fields);
	}

	/**
	 * Test to_fields_array returns proper structure.
	 */
	public function test_to_fields_array(): void {
		$attributes = [
			'id'              => 'terms_checkbox',
			'name'            => 'I agree to the terms',
			'tooltip'         => 'You must agree to continue',
			'required'        => true,
			'element_classes' => 'custom-class',
			'default_state'   => false,
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('terms_checkbox', $fields);
		$this->assertEquals('checkbox', $fields['terms_checkbox']['type']);
		$this->assertEquals('I agree to the terms', $fields['terms_checkbox']['name']);
		$this->assertTrue($fields['terms_checkbox']['required']);
	}

	/**
	 * Test to_fields_array with default state enabled.
	 */
	public function test_to_fields_array_with_default_state(): void {
		$attributes = [
			'id'              => 'subscribe_checkbox',
			'name'            => 'Subscribe to newsletter',
			'tooltip'         => '',
			'required'        => false,
			'element_classes' => '',
			'default_state'   => true,
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('subscribe_checkbox', $fields);
		$this->assertArrayHasKey('html_attr', $fields['subscribe_checkbox']);
		$this->assertEquals('checked', $fields['subscribe_checkbox']['html_attr']['checked']);
	}
}
