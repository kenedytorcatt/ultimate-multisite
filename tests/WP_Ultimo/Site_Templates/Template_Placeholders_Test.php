<?php

namespace WP_Ultimo\Site_Templates;

/**
 * Tests for the Template_Placeholders class.
 */
class Template_Placeholders_Test extends \WP_UnitTestCase {

	/**
	 * Get a fresh Template_Placeholders instance.
	 *
	 * @return Template_Placeholders
	 */
	private function get_instance() {

		return Template_Placeholders::get_instance();
	}

	public function set_up() {

		parent::set_up();

		// Set up default placeholders option
		wu_save_option('template_placeholders', [
			'placeholders' => [
				[
					'placeholder' => 'site_name',
					'content'     => 'My Site',
				],
				[
					'placeholder' => 'admin_email',
					'content'     => 'admin@example.com',
				],
			],
		]);
	}

	public function tear_down() {

		wu_delete_option('template_placeholders');

		parent::tear_down();
	}

	/**
	 * Test singleton instance.
	 */
	public function test_get_instance() {

		$instance = $this->get_instance();

		$this->assertInstanceOf(Template_Placeholders::class, $instance);
		$this->assertSame($instance, Template_Placeholders::get_instance());
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks() {

		$instance = $this->get_instance();

		$instance->init();

		$this->assertNotFalse(has_action('wp_ultimo_admin_pages', [$instance, 'add_template_placeholders_admin_page']));
		$this->assertNotFalse(has_action('wp_ajax_wu_get_placeholders', [$instance, 'serve_placeholders_via_ajax']));
		$this->assertNotFalse(has_action('wp_ajax_wu_save_placeholders', [$instance, 'save_placeholders']));
		$this->assertNotFalse(has_filter('the_content', [$instance, 'placeholder_replacer']));
		$this->assertNotFalse(has_filter('the_title', [$instance, 'placeholder_replacer']));
	}

	/**
	 * Test add_curly_braces wraps tag in braces.
	 */
	public function test_add_curly_braces() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'add_curly_braces');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$result = $ref->invoke($instance, 'test_tag');

		$this->assertSame('{{test_tag}}', $result);
	}

	/**
	 * Test placeholder_replacer replaces placeholders.
	 */
	public function test_placeholder_replacer() {

		$instance = $this->get_instance();

		// Re-init to load placeholders
		$instance->init();

		$content = 'Welcome to {{site_name}}. Contact us at {{admin_email}}.';
		$result  = $instance->placeholder_replacer($content);

		$this->assertStringNotContainsString('{{site_name}}', $result);
		$this->assertStringNotContainsString('{{admin_email}}', $result);
		$this->assertStringContainsString('My Site', $result);
		$this->assertStringContainsString('admin@example.com', $result);
	}

	/**
	 * Test placeholder_replacer handles content without placeholders.
	 */
	public function test_placeholder_replacer_no_placeholders() {

		$instance = $this->get_instance();

		$instance->init();

		$content = 'Plain content without placeholders.';
		$result  = $instance->placeholder_replacer($content);

		$this->assertSame($content, $result);
	}

	/**
	 * Test placeholder_replacer preserves unmatched placeholders.
	 */
	public function test_placeholder_replacer_preserves_unmatched() {

		$instance = $this->get_instance();

		$instance->init();

		$content = 'Welcome to {{site_name}}. Your code: {{unmatched_placeholder}}.';
		$result  = $instance->placeholder_replacer($content);

		$this->assertStringContainsString('My Site', $result);
		// Unmatched placeholder should remain
		$this->assertStringContainsString('{{unmatched_placeholder}}', $result);
	}

	/**
	 * Test load_placeholders with empty option.
	 */
	public function test_load_placeholders_empty() {

		wu_delete_option('template_placeholders');

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'load_placeholders');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		// Should not throw with empty option
		$ref->invoke($instance);

		$this->assertTrue(true);
	}

	/**
	 * Test serve_placeholders_via_ajax method exists.
	 */
	public function test_serve_placeholders_via_ajax_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'serve_placeholders_via_ajax'));
	}

	/**
	 * Test save_placeholders method exists.
	 */
	public function test_save_placeholders_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'save_placeholders'));
	}

	/**
	 * Test add_template_placeholders_admin_page method exists.
	 */
	public function test_add_template_placeholders_admin_page_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'add_template_placeholders_admin_page'));
	}

	/**
	 * Test render_install_user_switching method exists on User_Switching.
	 */
	public function test_placeholder_values_are_cached() {

		$instance = $this->get_instance();

		$instance->init();

		// Access placeholder_values via reflection
		$ref = new \ReflectionProperty($instance, 'placeholder_values');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$values = $ref->getValue($instance);

		// Values should be processed (nl2br applied)
		$this->assertIsArray($values);
		$this->assertNotEmpty($values);
	}

	/**
	 * Test placeholder_keys are wrapped in braces.
	 */
	public function test_placeholder_keys_have_braces() {

		$instance = $this->get_instance();

		$instance->init();

		$ref = new \ReflectionProperty($instance, 'placeholder_keys');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$keys = $ref->getValue($instance);

		foreach ($keys as $key) {
			$this->assertStringStartsWith('{{', $key);
			$this->assertStringEndsWith('}}', $key);
		}
	}

	/**
	 * Test placeholders array combines keys and values.
	 */
	public function test_placeholders_array_combined() {

		$instance = $this->get_instance();

		$instance->init();

		$ref = new \ReflectionProperty($instance, 'placeholders');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$placeholders = $ref->getValue($instance);

		$this->assertIsArray($placeholders);

		// Check that keys are wrapped in braces
		foreach ($placeholders as $key => $value) {
			$this->assertStringStartsWith('{{', $key);
			$this->assertStringEndsWith('}}', $key);
		}
	}
}
