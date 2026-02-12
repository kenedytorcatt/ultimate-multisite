<?php
/**
 * Tests for Signup_Field_Site_Title class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Site_Title.
 */
class Signup_Field_Site_Title_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Site_Title
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Site_Title();
	}

	/**
	 * Test get_type returns site_title.
	 */
	public function test_get_type(): void {
		$this->assertEquals('site_title', $this->field->get_type());
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
		$this->assertEquals('Site Title', $title);
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
	 * Test is_site_field returns true.
	 */
	public function test_is_site_field(): void {
		$this->assertTrue($this->field->is_site_field());
	}

	/**
	 * Test is_user_field returns false.
	 */
	public function test_is_user_field(): void {
		$this->assertFalse($this->field->is_user_field());
	}

	/**
	 * Test defaults returns array with auto_generate_site_title.
	 */
	public function test_defaults(): void {
		$defaults = $this->field->defaults();
		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('auto_generate_site_title', $defaults);
		$this->assertFalse($defaults['auto_generate_site_title']);
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
		$this->assertEquals('site_title', $forced['id']);
		$this->assertArrayHasKey('required', $forced);
		$this->assertTrue($forced['required']);
	}

	/**
	 * Test get_fields returns array with auto_generate option.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
		$this->assertArrayHasKey('auto_generate_site_title', $fields);
	}

	/**
	 * Test to_fields_array returns text field when not auto-generating.
	 */
	public function test_to_fields_array_regular(): void {
		$attributes = [
			'id'                       => 'site_title',
			'name'                     => 'Site Title',
			'placeholder'              => 'Enter site title',
			'tooltip'                  => 'Your site name',
			'auto_generate_site_title' => false,
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('site_title', $fields);
		$this->assertEquals('text', $fields['site_title']['type']);
		$this->assertEquals('Site Title', $fields['site_title']['name']);
		$this->assertTrue($fields['site_title']['required']);
	}

	/**
	 * Test to_fields_array returns hidden fields when auto-generating.
	 */
	public function test_to_fields_array_auto_generate(): void {
		$attributes = [
			'name'                     => 'Site Title',
			'placeholder'              => 'Enter site title',
			'tooltip'                  => '',
			'auto_generate_site_title' => true,
		];

		$this->field->set_attributes($attributes);
		$fields = $this->field->to_fields_array($attributes);

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('auto_generate_site_title', $fields);
		$this->assertArrayHasKey('site_title', $fields);
		$this->assertEquals('hidden', $fields['site_title']['type']);
		$this->assertEquals('autogenerate', $fields['site_title']['value']);
	}
}
