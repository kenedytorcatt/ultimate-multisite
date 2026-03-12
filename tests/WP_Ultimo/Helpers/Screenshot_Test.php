<?php
/**
 * Tests for the Screenshot helper class.
 */

namespace WP_Ultimo\Helpers;

use WP_UnitTestCase;

/**
 * @group screenshot
 */
class Screenshot_Test extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters('wu_screenshot_api_url');
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// api_url
	// ------------------------------------------------------------------

	public function test_api_url_returns_string() {
		$url = Screenshot::api_url('example.com');
		$this->assertIsString($url);
	}

	public function test_api_url_contains_domain() {
		$url = Screenshot::api_url('example.com');
		$this->assertStringContainsString('example.com', $url);
	}

	public function test_api_url_uses_thum_io() {
		$url = Screenshot::api_url('example.com');
		$this->assertStringContainsString('thum.io', $url);
	}

	public function test_api_url_includes_width_parameter() {
		$url = Screenshot::api_url('example.com');
		$this->assertStringContainsString('width/1280', $url);
	}

	public function test_api_url_includes_crop_parameter() {
		$url = Screenshot::api_url('example.com');
		$this->assertStringContainsString('crop/960', $url);
	}

	public function test_api_url_includes_noanimate() {
		$url = Screenshot::api_url('example.com');
		$this->assertStringContainsString('noanimate', $url);
	}

	public function test_api_url_filter_can_override() {
		add_filter('wu_screenshot_api_url', function ($url, $domain) {
			return 'https://custom-screenshot.com/' . $domain;
		}, 10, 2);

		$url = Screenshot::api_url('example.com');
		$this->assertEquals('https://custom-screenshot.com/example.com', $url);
	}

	// ------------------------------------------------------------------
	// take_screenshot
	// ------------------------------------------------------------------

	public function test_take_screenshot_returns_false_for_invalid_url() {
		// Will fail because the API won't return a valid PNG
		add_filter('pre_http_request', function () {
			return [
				'response' => ['code' => 200, 'message' => 'OK'],
				'body'     => 'not a png',
			];
		});

		$result = Screenshot::take_screenshot('http://nonexistent.invalid');
		$this->assertFalse($result);

		remove_all_filters('pre_http_request');
	}

	public function test_take_screenshot_returns_false_on_http_error() {
		add_filter('pre_http_request', function () {
			return [
				'response' => ['code' => 500, 'message' => 'Server Error'],
				'body'     => '',
			];
		});

		$result = Screenshot::take_screenshot('http://example.com');
		$this->assertFalse($result);

		remove_all_filters('pre_http_request');
	}
}
