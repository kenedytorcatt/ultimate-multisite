<?php
/**
 * Tests for Signup_Field_Username class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Checkout\Signup_Fields;

use WP_UnitTestCase;

/**
 * Test class for Signup_Field_Username.
 */
class Signup_Field_Username_Test extends WP_UnitTestCase {

	/**
	 * @var Signup_Field_Username
	 */
	private $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new Signup_Field_Username();
	}

	/**
	 * Test get_type returns username.
	 */
	public function test_get_type(): void {
		$this->assertEquals('username', $this->field->get_type());
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
		$this->assertNotEmpty($title);
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
	 * Test get_icon returns dashicon class.
	 */
	public function test_get_icon(): void {
		$icon = $this->field->get_icon();
		$this->assertIsString($icon);
	}

	/**
	 * Test is_user_field returns true.
	 */
	public function test_is_user_field(): void {
		$this->assertTrue($this->field->is_user_field());
	}

	/**
	 * Test is_site_field returns false.
	 */
	public function test_is_site_field(): void {
		$this->assertFalse($this->field->is_site_field());
	}

	/**
	 * Test default_fields contains expected fields.
	 */
	public function test_default_fields(): void {
		$fields = $this->field->default_fields();
		$this->assertIsArray($fields);
	}

	/**
	 * Test get_fields returns array.
	 */
	public function test_get_fields(): void {
		$fields = $this->field->get_fields();
		$this->assertIsArray($fields);
	}
}
