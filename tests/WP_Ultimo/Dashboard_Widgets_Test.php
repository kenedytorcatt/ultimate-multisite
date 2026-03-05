<?php
/**
 * Unit tests for Dashboard_Widgets class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Tests;

use WP_Ultimo\Dashboard_Widgets;

class Dashboard_Widgets_Test extends \WP_UnitTestCase {

	/**
	 * Test singleton instance is returned correctly.
	 */
	public function test_singleton_returns_instance(): void {

		$instance = Dashboard_Widgets::get_instance();

		$this->assertInstanceOf(Dashboard_Widgets::class, $instance);
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$instance = Dashboard_Widgets::get_instance();
		$instance->init();

		$this->assertNotFalse(has_action('admin_enqueue_scripts', [$instance, 'enqueue_scripts']));
		$this->assertNotFalse(has_action('wp_network_dashboard_setup', [$instance, 'register_network_widgets']));
		$this->assertNotFalse(has_action('wp_dashboard_setup', [$instance, 'register_widgets']));
		$this->assertNotFalse(has_action('wp_ajax_wu_fetch_rss', [$instance, 'process_ajax_fetch_rss']));
		$this->assertNotFalse(has_action('wp_ajax_wu_fetch_activity', [$instance, 'process_ajax_fetch_events']));
		$this->assertNotFalse(has_action('wp_ajax_wu_generate_csv', [$instance, 'handle_table_csv']));
	}

	/**
	 * Test screen_id property is set correctly.
	 */
	public function test_screen_id_property(): void {

		$instance = Dashboard_Widgets::get_instance();

		$this->assertEquals('dashboard-network', $instance->screen_id);
	}
}