<?php
/**
 * Tests for Form class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\UI;

use WP_UnitTestCase;

/**
 * Test class for Form.
 */
class Form_Test extends WP_UnitTestCase {

	/**
	 * Test constructor creates form with attributes.
	 */
	public function test_constructor(): void {
		$form = new Form('test_form', []);

		$atts = $form->get_attributes();
		$this->assertEquals('test_form', $atts['id']);
		$this->assertEquals('post', $atts['method']);
	}

	/**
	 * Test constructor with custom attributes.
	 */
	public function test_constructor_with_custom_attributes(): void {
		$form = new Form('test_form', [], [
			'method' => 'get',
			'title'  => 'Test Form',
		]);

		$atts = $form->get_attributes();
		$this->assertEquals('get', $atts['method']);
		$this->assertEquals('Test Form', $atts['title']);
	}

	/**
	 * Test default attribute values.
	 */
	public function test_default_attribute_values(): void {
		$form = new Form('test_form', []);

		$atts = $form->get_attributes();
		$this->assertEquals('post', $atts['method']);
		$this->assertEquals('', $atts['before']);
		$this->assertEquals('', $atts['after']);
		$this->assertFalse($atts['action']);
		$this->assertFalse($atts['title']);
		$this->assertFalse($atts['wrap_in_form_tag']);
		$this->assertEquals('div', $atts['wrap_tag']);
		$this->assertEquals('settings/fields', $atts['views']);
	}

	/**
	 * Test get_fields returns array.
	 */
	public function test_get_fields_returns_array(): void {
		$form = new Form('test_form', []);

		$fields = $form->get_fields();
		$this->assertIsArray($fields);
	}

	/**
	 * Test get_fields with fields.
	 */
	public function test_get_fields_with_fields(): void {
		$form = new Form('test_form', [
			'field1' => [
				'type'  => 'text',
				'title' => 'Field 1',
			],
			'field2' => [
				'type'  => 'number',
				'title' => 'Field 2',
			],
		]);

		$fields = $form->get_fields();
		$this->assertCount(2, $fields);
	}

	/**
	 * Test magic getter returns attribute.
	 */
	public function test_magic_getter(): void {
		$form = new Form('test_form', [], [
			'title' => 'My Form Title',
		]);

		$this->assertEquals('My Form Title', $form->title);
		$this->assertEquals('test_form', $form->id);
		$this->assertEquals('post', $form->method);
	}

	/**
	 * Test magic getter returns false for missing attribute.
	 */
	public function test_magic_getter_missing_attribute(): void {
		$form = new Form('test_form', []);

		$this->assertFalse($form->nonexistent);
	}

	/**
	 * Test get_attributes returns array.
	 */
	public function test_get_attributes_returns_array(): void {
		$form = new Form('test_form', []);

		$atts = $form->get_attributes();
		$this->assertIsArray($atts);
	}

	/**
	 * Test form with wrap_in_form_tag.
	 */
	public function test_form_with_wrap_in_form_tag(): void {
		$form = new Form('test_form', [], [
			'wrap_in_form_tag' => true,
		]);

		$atts = $form->get_attributes();
		$this->assertTrue($atts['wrap_in_form_tag']);
	}

	/**
	 * Test form with custom classes.
	 */
	public function test_form_with_custom_classes(): void {
		$form = new Form('test_form', [], [
			'classes'               => 'custom-form-class',
			'field_wrapper_classes' => 'custom-wrapper',
			'field_classes'         => 'custom-field',
		]);

		$atts = $form->get_attributes();
		$this->assertEquals('custom-form-class', $atts['classes']);
		$this->assertEquals('custom-wrapper', $atts['field_wrapper_classes']);
		$this->assertEquals('custom-field', $atts['field_classes']);
	}

	/**
	 * Test form with html_attr.
	 */
	public function test_form_with_html_attr(): void {
		$form = new Form('test_form', [], [
			'html_attr' => [
				'class'       => 'my-class',
				'data-custom' => 'value',
			],
		]);

		$atts = $form->get_attributes();
		$this->assertIsArray($atts['html_attr']);
		$this->assertEquals('my-class', $atts['html_attr']['class']);
		$this->assertEquals('value', $atts['html_attr']['data-custom']);
	}

	/**
	 * Test form with action.
	 */
	public function test_form_with_action(): void {
		$form = new Form('test_form', [], [
			'action' => 'my_action_url',
		]);

		$this->assertEquals('my_action_url', $form->action);
	}

	/**
	 * Test form jsonSerialize.
	 */
	public function test_json_serialize(): void {
		$form = new Form('test_form', [
			'field1' => ['type' => 'text'],
		], [
			'title' => 'Test',
		]);

		$json = json_encode($form);

		$this->assertIsString($json);
		$this->assertJson($json);
	}

	/**
	 * Test form with variables.
	 */
	public function test_form_with_variables(): void {
		$form = new Form('test_form', [], [
			'variables' => [
				'custom_var' => 'custom_value',
			],
		]);

		$atts = $form->get_attributes();
		$this->assertIsArray($atts['variables']);
		$this->assertEquals('custom_value', $atts['variables']['custom_var']);
	}

	/**
	 * Test form with step.
	 */
	public function test_form_with_step(): void {
		$form = new Form('test_form', [], [
			'step' => (object) [
				'classes'    => 'step-class',
				'element_id' => 'step-1',
			],
		]);

		$atts = $form->get_attributes();
		$this->assertEquals('step-class', $atts['step']->classes);
		$this->assertEquals('step-1', $atts['step']->element_id);
	}

	/**
	 * Test form with custom views.
	 */
	public function test_form_with_custom_views(): void {
		$form = new Form('test_form', [], [
			'views' => 'custom/fields',
		]);

		$this->assertEquals('custom/fields', $form->views);
	}

	/**
	 * Test form fields are Field instances.
	 */
	public function test_form_fields_are_field_instances(): void {
		$form = new Form('test_form', [
			'my_field' => [
				'type'  => 'text',
				'title' => 'My Field',
			],
		]);

		$fields = $form->get_fields();
		$this->assertCount(1, $fields);

		$field = reset($fields);
		$this->assertInstanceOf(Field::class, $field);
	}

	/**
	 * Test callable before attribute.
	 */
	public function test_callable_before_attribute(): void {
		$form = new Form('test_form', [], [
			'before' => function ($form) {
				return '<div class="before">';
			},
		]);

		$this->assertEquals('<div class="before">', $form->before);
	}

	/**
	 * Test callable after attribute.
	 */
	public function test_callable_after_attribute(): void {
		$form = new Form('test_form', [], [
			'after' => function ($form) {
				return '</div>';
			},
		]);

		$this->assertEquals('</div>', $form->after);
	}
}
