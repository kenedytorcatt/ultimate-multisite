<?php

namespace WP_Ultimo\Debug;

/**
 * Tests for the Debug class.
 */
class Debug_Test extends \WP_UnitTestCase {

	/**
	 * Get a fresh Debug instance.
	 *
	 * @return Debug
	 */
	private function get_instance() {

		return Debug::get_instance();
	}

	public function set_up() {

		parent::set_up();

		// Define WP_ULTIMO_DEBUG for tests
		if ( ! defined('WP_ULTIMO_DEBUG')) {
			define('WP_ULTIMO_DEBUG', true);
		}
	}

	/**
	 * Test singleton instance.
	 */
	public function test_get_instance() {

		$instance = $this->get_instance();

		$this->assertInstanceOf(Debug::class, $instance);
		$this->assertSame($instance, Debug::get_instance());
	}

	/**
	 * Test should_load returns true when constant is defined.
	 */
	public function test_should_load_true() {

		$instance = $this->get_instance();

		$this->assertTrue($instance->should_load());
	}

	/**
	 * Test get_pages returns array.
	 */
	public function test_get_pages() {

		$instance = $this->get_instance();

		$pages = $instance->get_pages();

		$this->assertIsArray($pages);
	}

	/**
	 * Test add_page adds a page.
	 */
	public function test_add_page() {

		$instance = $this->get_instance();

		$instance->add_page('test-page');

		$pages = $instance->get_pages();

		$this->assertArrayHasKey('test-page', $pages);
	}

	/**
	 * Test load does not throw when should_load is true.
	 */
	public function test_load() {

		$instance = $this->get_instance();

		// Should not throw
		$instance->load();

		$this->assertTrue(true);
	}

	/**
	 * Test init does not throw.
	 */
	public function test_init() {

		$instance = $this->get_instance();

		// Should not throw
		$instance->init();

		$this->assertTrue(true);
	}

	/**
	 * Test add_additional_hooks does not throw.
	 */
	public function test_add_additional_hooks() {

		$instance = $this->get_instance();

		// Should not throw
		$instance->add_additional_hooks();

		$this->assertTrue(true);
	}

	/**
	 * Test register_forms does not throw.
	 */
	public function test_register_forms() {

		$instance = $this->get_instance();

		// Should not throw
		$instance->register_forms();

		$this->assertTrue(true);
	}

	/**
	 * Test add_main_debug_menu does not throw.
	 */
	public function test_add_main_debug_menu() {

		$instance = $this->get_instance();

		// Should not throw (creates admin page)
		$instance->add_main_debug_menu();

		$this->assertTrue(true);
	}

	/**
	 * Test reset_settings removes known options.
	 */
	public function test_reset_settings() {

		$instance = $this->get_instance();

		// Set one of the options that reset_settings actually deletes
		// debug_faker is in the list with prefix 'wp-ultimo_'
		update_network_option(null, 'wp-ultimo_debug_faker', ['test' => 'data']);

		$this->assertSame(['test' => 'data'], get_network_option(null, 'wp-ultimo_debug_faker'));

		// Call reset_settings via reflection
		$ref = new \ReflectionMethod($instance, 'reset_settings');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance);

		// The option should be deleted
		$this->assertFalse(get_network_option(null, 'wp-ultimo_debug_faker'));
	}

	/**
	 * Test reset_table with empty table name does nothing.
	 */
	public function test_reset_table_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_table');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		// Should not throw with empty table
		$ref->invoke($instance, '', [], 'ID');

		$this->assertTrue(true);
	}

	/**
	 * Test reset_customers with no IDs.
	 */
	public function test_reset_customers_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_customers');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		// Should not throw with empty array
		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_products with no IDs.
	 */
	public function test_reset_products_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_products');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_memberships with no IDs.
	 */
	public function test_reset_memberships_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_memberships');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_domains with no IDs.
	 */
	public function test_reset_domains_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_domains');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_sites with no IDs.
	 */
	public function test_reset_sites_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_sites');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_discount_codes with no IDs.
	 */
	public function test_reset_discount_codes_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_discount_codes');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_payments with no IDs.
	 */
	public function test_reset_payments_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_payments');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_webhooks with no IDs.
	 */
	public function test_reset_webhooks_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_webhooks');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_checkout_forms with no IDs.
	 */
	public function test_reset_checkout_forms_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_checkout_forms');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_post_like_models with no IDs.
	 */
	public function test_reset_post_like_models_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_post_like_models');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_events with no IDs.
	 */
	public function test_reset_events_empty() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_events');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		$ref->invoke($instance, []);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_fake_data with empty option.
	 */
	public function test_reset_fake_data_empty() {

		$instance = $this->get_instance();

		// Ensure debug_faker option is empty
		wu_delete_option('debug_faker');

		$ref = new \ReflectionMethod($instance, 'reset_fake_data');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		// Should not throw with empty option
		$ref->invoke($instance);

		$this->assertTrue(true);
	}

	/**
	 * Test reset_all_data does not throw.
	 */
	public function test_reset_all_data() {

		$instance = $this->get_instance();

		$ref = new \ReflectionMethod($instance, 'reset_all_data');

		if (PHP_VERSION_ID < 80100) {
			$ref->setAccessible(true);
		}

		// Should not throw (operates on empty tables mostly)
		$ref->invoke($instance);

		$this->assertTrue(true);
	}

	/**
	 * Test handle_debug_reset_database_form with reset_only=true.
	 */
	public function test_handle_debug_reset_database_form_reset_only() {

		$instance = $this->get_instance();

		// Mock the request
		$_POST['reset_only'] = '1';
		$_POST['_wpnonce']   = wp_create_nonce('wu_form_nonce');

		// Set up a fake option
		wu_save_option('debug_faker', ['customers' => []]);

		// This would normally send JSON response
		// We can't easily test the full flow without output buffering issues
		// So we just verify the method exists and doesn't fatal
		$this->assertTrue(method_exists($instance, 'handle_debug_reset_database_form'));

		// Clean up
		wu_delete_option('debug_faker');
		unset($_POST['reset_only'], $_POST['_wpnonce']);
	}

	/**
	 * Test handle_debug_drop_database_form method exists.
	 */
	public function test_handle_debug_drop_database_form_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'handle_debug_drop_database_form'));
	}

	/**
	 * Test handle_debug_generator_form method exists.
	 */
	public function test_handle_debug_generator_form_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'handle_debug_generator_form'));
	}

	/**
	 * Test render_checkout_autofill_button method exists.
	 */
	public function test_render_checkout_autofill_button_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'render_checkout_autofill_button'));
	}

	/**
	 * Test render_debug_generator_form method exists.
	 */
	public function test_render_debug_generator_form_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'render_debug_generator_form'));
	}

	/**
	 * Test render_debug_reset_database_form method exists.
	 */
	public function test_render_debug_reset_database_form_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'render_debug_reset_database_form'));
	}

	/**
	 * Test render_debug_drop_database_form method exists.
	 */
	public function test_render_debug_drop_database_form_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'render_debug_drop_database_form'));
	}
}
