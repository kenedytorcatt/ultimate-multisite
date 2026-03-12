<?php
/**
 * Tests for template functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for template functions.
 */
class Template_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_template_contents returns string.
	 */
	public function test_wu_get_template_contents_returns_string(): void {

		// Use a view that exists in the plugin.
		$result = wu_get_template_contents('base/empty-state', [
			'message'                  => 'Test message',
			'sub_message'              => 'Sub message',
			'link_label'               => 'Go Back',
			'link_url'                 => '#',
			'link_classes'             => '',
			'link_icon'                => '',
			'display_background_image' => false,
		]);

		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_template_contents with nonexistent view and default.
	 */
	public function test_wu_get_template_contents_with_default_view(): void {

		$result = wu_get_template_contents('nonexistent/view', [
			'message'                  => 'Fallback',
			'sub_message'              => 'Fallback sub',
			'link_label'               => 'Back',
			'link_url'                 => '#',
			'link_classes'             => '',
			'link_icon'                => '',
			'display_background_image' => false,
		], 'base/empty-state');

		// Should fall back to the default view.
		$this->assertIsString($result);
	}

	/**
	 * Test wp_ultimo_render_vars filter is applied.
	 */
	public function test_render_vars_filter_applied(): void {

		$filter_called = false;

		add_filter(
			'wp_ultimo_render_vars',
			function ($args) use (&$filter_called) {
				$filter_called = true;
				return $args;
			}
		);

		wu_get_template_contents('base/empty-state', [
			'message'                  => 'Test',
			'sub_message'              => 'Test',
			'link_label'               => 'Back',
			'link_url'                 => '#',
			'link_classes'             => '',
			'link_icon'                => '',
			'display_background_image' => false,
		]);

		$this->assertTrue($filter_called);

		remove_all_filters('wp_ultimo_render_vars');
	}
}
