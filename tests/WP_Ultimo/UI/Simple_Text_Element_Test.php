<?php
/**
 * Tests for Simple_Text_Element class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\UI;

use WP_UnitTestCase;

/**
 * Test class for Simple_Text_Element.
 */
class Simple_Text_Element_Test extends WP_UnitTestCase {

	/**
	 * @var Simple_Text_Element
	 */
	private $element;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->element = Simple_Text_Element::get_instance();
	}

	/**
	 * Test get_instance returns singleton.
	 */
	public function test_get_instance(): void {
		$instance1 = Simple_Text_Element::get_instance();
		$instance2 = Simple_Text_Element::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test element id is simple-text.
	 */
	public function test_element_id(): void {
		$this->assertEquals('simple-text', $this->element->id);
	}

	/**
	 * Test get_title returns string.
	 */
	public function test_get_title(): void {
		$title = $this->element->get_title();

		$this->assertIsString($title);
		$this->assertEquals('Simple Text', $title);
	}

	/**
	 * Test get_description returns string.
	 */
	public function test_get_description(): void {
		$description = $this->element->get_description();

		$this->assertIsString($description);
		$this->assertNotEmpty($description);
	}

	/**
	 * Test get_icon returns string for block context.
	 */
	public function test_get_icon_block(): void {
		$icon = $this->element->get_icon('block');

		$this->assertIsString($icon);
		$this->assertEquals('fa fa-search', $icon);
	}

	/**
	 * Test get_icon returns elementor icon.
	 */
	public function test_get_icon_elementor(): void {
		$icon = $this->element->get_icon('elementor');

		$this->assertIsString($icon);
		$this->assertEquals('eicon-lock-user', $icon);
	}

	/**
	 * Test fields returns array.
	 */
	public function test_fields(): void {
		$fields = $this->element->fields();

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('header', $fields);
		$this->assertArrayHasKey('simple_text', $fields);
	}

	/**
	 * Test fields has header field.
	 */
	public function test_fields_has_header(): void {
		$fields = $this->element->fields();

		$this->assertEquals('header', $fields['header']['type']);
		$this->assertEquals('General', $fields['header']['title']);
	}

	/**
	 * Test fields has simple_text field.
	 */
	public function test_fields_has_simple_text(): void {
		$fields = $this->element->fields();

		$this->assertEquals('textarea', $fields['simple_text']['type']);
		$this->assertArrayHasKey('title', $fields['simple_text']);
		$this->assertArrayHasKey('placeholder', $fields['simple_text']);
	}

	/**
	 * Test keywords returns array.
	 */
	public function test_keywords(): void {
		$keywords = $this->element->keywords();

		$this->assertIsArray($keywords);
		$this->assertContains('WP Ultimo', $keywords);
		$this->assertContains('Ultimate Multisite', $keywords);
		$this->assertContains('text', $keywords);
		$this->assertContains('simple text', $keywords);
		$this->assertContains('shortcode', $keywords);
	}

	/**
	 * Test defaults returns array.
	 */
	public function test_defaults(): void {
		$defaults = $this->element->defaults();

		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('simple_text', $defaults);
	}

	/**
	 * Test defaults has simple_text default.
	 */
	public function test_defaults_has_simple_text(): void {
		$defaults = $this->element->defaults();

		$this->assertIsString($defaults['simple_text']);
		$this->assertNotEmpty($defaults['simple_text']);
	}

	/**
	 * Test element is public.
	 */
	public function test_element_is_public(): void {
		$reflection = new \ReflectionClass($this->element);
		$property   = $reflection->getProperty('public');
		$property->setAccessible(true);

		$this->assertTrue($property->getValue($this->element));
	}

	/**
	 * Test element is hidden by default.
	 */
	public function test_element_hidden_by_default(): void {
		$reflection = new \ReflectionClass($this->element);
		$property   = $reflection->getProperty('hidden_by_default');
		$property->setAccessible(true);

		$this->assertTrue($property->getValue($this->element));
	}
}
