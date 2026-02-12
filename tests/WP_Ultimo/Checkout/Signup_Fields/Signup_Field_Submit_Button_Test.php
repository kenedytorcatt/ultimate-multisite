<?php
/**
 * Tests for Signup_Field_Submit_Button class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Submit_Button.
 */
class Signup_Field_Submit_Button_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Submit_Button
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Submit_Button();
	}

	/**
	 * Test get_type returns submit_button.
	 */
	public function test_get_type(): void {
		$this->assertEquals('submit_button', $this->field->get_type());
	}

	/**
	 * Test is_required returns true.
	 */
	public function test_is_required(): void {
		$this->assertTrue($this->field->is_required());
	}

	/**
	 * Test get_title returns string.
	 */
	public function test_get_title(): void {
		$title = $this->field->get_title();
		$this->assertIsString($title);
		$this->assertEquals('Submit Button', $title);
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
	 * Test defaults returns array.
	 */
	public function test_defaults(): void {
		$defaults = $this->field->defaults();
		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('enable_go_back_button', $defaults);
		$this->assertArrayHasKey('back_button_label', $defaults);
	}

	/**
	 * Test defaults has correct values.
	 */
	public function test_defaults_values(): void {
		$defaults = $this->field->defaults();
		$this->assertFalse($defaults['enable_go_back_button']);
		$this->assertIsString($defaults['back_button_label']);
	}

	/**
	 * Test default_fields returns expected fields.
	 */
	public function test_default_fields(): void {
		$fields = $this->field->default_fields();
		$this->assertIsArray($fields);
		$this->assertContains('id', $fields);
		$this->assertContains('name', $fields);
	}

	/**
	 * Test get_fields returns array.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
		$this->assertArrayHasKey('enable_go_back_button', $fields);
		$this->assertArrayHasKey('back_button_label', $fields);
	}

	/**
	 * Test get_fields structure.
	 */
	public function test_get_fields_structure(): void {
		$fields = $this->field->get_fields();

		$this->assertEquals('toggle', $fields['enable_go_back_button']['type']);
		$this->assertEquals('text', $fields['back_button_label']['type']);
	}

	/**
	 * Test to_fields_array returns proper structure.
	 */
	public function test_to_fields_array(): void {
		$attributes = [
			'id'                    => 'submit_btn',
			'name'                  => 'Submit',
			'step'                  => 'checkout',
			'enable_go_back_button' => false,
			'back_button_label'     => 'Go Back',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('submit_btn_errors', $fields);
		$this->assertArrayHasKey('submit_btn_group', $fields);
	}

	/**
	 * Test to_fields_array has error field.
	 */
	public function test_to_fields_array_error_field(): void {
		$attributes = [
			'id'                    => 'submit_btn',
			'name'                  => 'Submit',
			'step'                  => 'checkout',
			'enable_go_back_button' => false,
			'back_button_label'     => 'Go Back',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertEquals('html', $fields['submit_btn_errors']['type']);
	}

	/**
	 * Test to_fields_array has group field.
	 */
	public function test_to_fields_array_group_field(): void {
		$attributes = [
			'id'                    => 'submit_btn',
			'name'                  => 'Submit',
			'step'                  => 'checkout',
			'enable_go_back_button' => false,
			'back_button_label'     => 'Go Back',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertEquals('group', $fields['submit_btn_group']['type']);
		$this->assertIsArray($fields['submit_btn_group']['fields']);
	}

	/**
	 * Test to_fields_array submit button in group.
	 */
	public function test_to_fields_array_submit_in_group(): void {
		$attributes = [
			'id'                    => 'submit_btn',
			'name'                  => 'Submit',
			'step'                  => 'checkout',
			'enable_go_back_button' => false,
			'back_button_label'     => 'Go Back',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$group_fields = $fields['submit_btn_group']['fields'];
		$this->assertArrayHasKey('submit_btn', $group_fields);
		$this->assertEquals('submit', $group_fields['submit_btn']['type']);
		$this->assertEquals('Submit', $group_fields['submit_btn']['name']);
	}

	/**
	 * Test to_fields_array with go back enabled.
	 */
	public function test_to_fields_array_with_go_back(): void {
		$attributes = [
			'id'                    => 'submit_btn',
			'name'                  => 'Submit',
			'step'                  => 'step_2',
			'enable_go_back_button' => true,
			'back_button_label'     => 'Go Back',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		// When go back is enabled, should have clear field
		$this->assertArrayHasKey('submit_btn_clear', $fields);
		$this->assertEquals('clear', $fields['submit_btn_clear']['type']);
	}
}
