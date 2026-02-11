<?php
/**
 * Tests for Signup_Field_Hidden class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Hidden.
 */
class Signup_Field_Hidden_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Hidden
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Hidden();
	}

	/**
	 * Test get_type returns hidden.
	 */
	public function test_get_type(): void {
		$this->assertEquals('hidden', $this->field->get_type());
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
		$this->assertEquals('Hidden Field', $title);
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
	 * Test defaults returns array with from_request.
	 */
	public function test_defaults(): void {
		$defaults = $this->field->defaults();
		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('from_request', $defaults);
		$this->assertTrue($defaults['from_request']);
	}

	/**
	 * Test default_fields contains expected fields.
	 */
	public function test_default_fields(): void {
		$fields = $this->field->default_fields();
		$this->assertIsArray($fields);
		$this->assertContains('id', $fields);
		$this->assertContains('save_as', $fields);
	}

	/**
	 * Test get_fields returns array with fixed_value.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
		$this->assertArrayHasKey('fixed_value', $fields);
	}

	/**
	 * Test to_fields_array returns proper structure.
	 */
	public function test_to_fields_array(): void {
		$attributes = [
			'id'              => 'tracking_id',
			'element_classes' => 'hidden-field',
			'fixed_value'     => 'abc123',
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('tracking_id', $fields);
		$this->assertEquals('hidden', $fields['tracking_id']['type']);
		$this->assertEquals('tracking_id', $fields['tracking_id']['id']);
	}

	/**
	 * Test get_value returns fixed_value when no other value.
	 */
	public function test_get_value_returns_fixed_value(): void {
		$attributes = [
			'id'          => 'test_hidden',
			'fixed_value' => 'fixed_test_value',
		];

		$this->field->set_attributes($attributes);
		$value = $this->field->get_value();

		$this->assertEquals('fixed_test_value', $value);
	}
}
