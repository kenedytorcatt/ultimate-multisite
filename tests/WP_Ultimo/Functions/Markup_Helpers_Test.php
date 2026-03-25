<?php
/**
 * Tests for markup helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for markup helper functions.
 */
class Markup_Helpers_Test extends WP_UnitTestCase {

	/**
	 * Test wu_convert_to_state with simple array.
	 */
	public function test_convert_to_state_simple_array(): void {

		$result = wu_convert_to_state(['key' => 'value']);

		$this->assertIsString($result);
		$decoded = json_decode($result, true);
		$this->assertEquals('value', $decoded['key']);
	}

	/**
	 * Test wu_convert_to_state with empty array.
	 */
	public function test_convert_to_state_empty_array(): void {

		$result = wu_convert_to_state([]);

		$this->assertIsString($result);
		$decoded = json_decode($result);
		$this->assertIsObject($decoded);
	}

	/**
	 * Test wu_convert_to_state with nested data.
	 */
	public function test_convert_to_state_nested_data(): void {

		$result = wu_convert_to_state([
			'name'    => 'test',
			'options' => ['a', 'b', 'c'],
		]);

		$decoded = json_decode($result, true);
		$this->assertEquals('test', $decoded['name']);
		$this->assertEquals(['a', 'b', 'c'], $decoded['options']);
	}

	/**
	 * Test wu_convert_to_state with default empty parameter.
	 */
	public function test_convert_to_state_default_parameter(): void {

		$result = wu_convert_to_state();

		$this->assertIsString($result);
		$this->assertEquals('{}', $result);
	}

	/**
	 * Test wu_remove_empty_p removes empty paragraphs.
	 */
	public function test_remove_empty_p_removes_empty_paragraphs(): void {

		$content = '<p>&nbsp;</p>';
		$result  = wu_remove_empty_p($content);

		$this->assertEquals('', $result);
	}

	/**
	 * Test wu_remove_empty_p removes p tags around block elements.
	 */
	public function test_remove_empty_p_removes_p_around_div(): void {

		$content = '<p><div class="test">Content</div></p>';
		$result  = wu_remove_empty_p($content);

		$this->assertStringContainsString('<div class="test">Content</div>', $result);
		$this->assertStringNotContainsString('<p><div', $result);
	}

	/**
	 * Test wu_remove_empty_p preserves non-empty paragraphs.
	 */
	public function test_remove_empty_p_preserves_content(): void {

		$content = '<p>This is real content</p>';
		$result  = wu_remove_empty_p($content);

		$this->assertEquals('<p>This is real content</p>', $result);
	}

	/**
	 * Test wu_remove_empty_p with section elements.
	 */
	public function test_remove_empty_p_with_section(): void {

		$content = '<p><section>Content</section></p>';
		$result  = wu_remove_empty_p($content);

		$this->assertStringContainsString('<section>Content</section>', $result);
	}

	/**
	 * Test wu_remove_empty_p with br after block elements.
	 */
	public function test_remove_empty_p_removes_br_after_block(): void {

		$content = '</div><br />';
		$result  = wu_remove_empty_p($content);

		$this->assertEquals('</div>', $result);
	}

	/**
	 * Test wu_array_to_html_attrs with simple attributes.
	 */
	public function test_array_to_html_attrs_simple(): void {

		$result = wu_array_to_html_attrs([
			'id'    => 'my-element',
			'class' => 'my-class',
		]);

		$this->assertStringContainsString('id="my-element"', $result);
		$this->assertStringContainsString('class="my-class"', $result);
	}

	/**
	 * Test wu_array_to_html_attrs with empty array.
	 */
	public function test_array_to_html_attrs_empty(): void {

		$result = wu_array_to_html_attrs([]);

		$this->assertEquals('', $result);
	}

	/**
	 * Test wu_array_to_html_attrs escapes values.
	 */
	public function test_array_to_html_attrs_escapes_values(): void {

		$result = wu_array_to_html_attrs([
			'data-value' => 'test"value',
		]);

		$this->assertStringNotContainsString('test"value', $result);
		$this->assertStringContainsString('data-value=', $result);
	}

	/**
	 * Test wu_print_html_attributes outputs attributes.
	 */
	public function test_print_html_attributes_outputs(): void {

		ob_start();
		wu_print_html_attributes([
			'id'    => 'test-id',
			'class' => 'test-class',
		]);
		$output = ob_get_clean();

		$this->assertStringContainsString('id="test-id"', $output);
		$this->assertStringContainsString('class="test-class"', $output);
	}

	/**
	 * Test wu_print_html_attributes with empty array.
	 */
	public function test_print_html_attributes_empty(): void {

		ob_start();
		wu_print_html_attributes([]);
		$output = ob_get_clean();

		$this->assertEquals('', $output);
	}

	/**
	 * Test wu_tooltip with valid tooltip text.
	 */
	public function test_tooltip_outputs_html(): void {

		ob_start();
		wu_tooltip('Help text here');
		$output = ob_get_clean();

		$this->assertStringContainsString('role="tooltip"', $output);
		$this->assertStringContainsString('aria-label="Help text here"', $output);
		$this->assertStringContainsString('wu-styling', $output);
	}

	/**
	 * Test wu_tooltip with empty string returns nothing.
	 */
	public function test_tooltip_empty_string_returns_nothing(): void {

		ob_start();
		wu_tooltip('');
		$output = ob_get_clean();

		$this->assertEquals('', $output);
	}

	/**
	 * Test wu_tooltip_text outputs correct attributes.
	 */
	public function test_tooltip_text_outputs_attributes(): void {

		ob_start();
		wu_tooltip_text('My tooltip');
		$output = ob_get_clean();

		$this->assertStringContainsString('role="tooltip"', $output);
		$this->assertStringContainsString('aria-label="My tooltip"', $output);
	}

	/**
	 * Test wu_preview_image returns correct HTML.
	 */
	public function test_preview_image_returns_html(): void {

		$result = wu_preview_image('https://example.com/image.png');

		$this->assertStringContainsString('data-image="https://example.com/image.png"', $result);
		$this->assertStringContainsString('wu-image-preview', $result);
	}

	/**
	 * Test wu_preview_image with custom label.
	 */
	public function test_preview_image_custom_label(): void {

		$result = wu_preview_image('https://example.com/image.png', 'View');

		$this->assertStringContainsString('View', $result);
	}

	/**
	 * Test wu_get_icons_list returns array with icon groups.
	 */
	public function test_get_icons_list_returns_array(): void {

		$icons = wu_get_icons_list();

		$this->assertIsArray($icons);
		$this->assertArrayHasKey('WP Ultimo Icons', $icons);
		$this->assertArrayHasKey('Dashicons', $icons);
	}

	/**
	 * Test wu_get_icons_list contains expected icons.
	 */
	public function test_get_icons_list_contains_expected_icons(): void {

		$icons = wu_get_icons_list();

		$this->assertContains('dashicons-wu-globe', $icons['WP Ultimo Icons']);
		$this->assertContains('dashicons-wu-cog', $icons['WP Ultimo Icons']);
	}

	/**
	 * Test wu_get_icons_list is filterable.
	 */
	public function test_get_icons_list_is_filterable(): void {

		add_filter('wu_icons_list', function ($icons) {
			$icons['Custom'] = ['custom-icon-1'];
			return $icons;
		});

		$icons = wu_get_icons_list();

		$this->assertArrayHasKey('Custom', $icons);
		$this->assertContains('custom-icon-1', $icons['Custom']);
	}

	/**
	 * Test wu_is_block_theme returns boolean.
	 */
	public function test_is_block_theme_returns_boolean(): void {

		$result = wu_is_block_theme();

		$this->assertIsBool($result);
	}

	/**
	 * Test wu_get_flag_emoji with valid country code.
	 */
	public function test_get_flag_emoji_valid_code(): void {

		$result = wu_get_flag_emoji('US');

		$this->assertNotEmpty($result);
		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_flag_emoji with lowercase input.
	 */
	public function test_get_flag_emoji_lowercase(): void {

		$upper = wu_get_flag_emoji('US');
		$lower = wu_get_flag_emoji('us');

		$this->assertEquals($upper, $lower);
	}

	/**
	 * Test wu_get_flag_emoji with invalid input.
	 */
	public function test_get_flag_emoji_invalid_input(): void {

		$this->assertEquals('', wu_get_flag_emoji(''));
		$this->assertEquals('', wu_get_flag_emoji('A'));
		$this->assertEquals('', wu_get_flag_emoji('ABC'));
		$this->assertEquals('', wu_get_flag_emoji('12'));
	}

	/**
	 * Test wu_get_flag_emoji produces different results for different countries.
	 */
	public function test_get_flag_emoji_different_countries(): void {

		$us = wu_get_flag_emoji('US');
		$gb = wu_get_flag_emoji('GB');
		$br = wu_get_flag_emoji('BR');

		$this->assertNotEquals($us, $gb);
		$this->assertNotEquals($us, $br);
		$this->assertNotEquals($gb, $br);
	}
}
