<?php
/**
 * Tests for asset helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for asset helper functions.
 */
class Assets_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_asset returns URL string.
	 */
	public function test_wu_get_asset_returns_url(): void {

		$result = wu_get_asset('logo.png');

		$this->assertIsString($result);
		$this->assertStringContainsString('assets/img/', $result);
	}

	/**
	 * Test wu_get_asset with custom directory.
	 */
	public function test_wu_get_asset_custom_dir(): void {

		$result = wu_get_asset('style.css', 'css');

		$this->assertIsString($result);
		$this->assertStringContainsString('assets/css/', $result);
	}

	/**
	 * Test wu_get_asset adds .min when SCRIPT_DEBUG is off.
	 */
	public function test_wu_get_asset_adds_min_suffix(): void {

		// SCRIPT_DEBUG is not defined or false in test env.
		$result = wu_get_asset('app.js', 'js');

		$this->assertStringContainsString('.min.js', $result);
	}

	/**
	 * Test wu_get_asset does not double-add .min.
	 */
	public function test_wu_get_asset_no_double_min(): void {

		$result = wu_get_asset('app.min.js', 'js');

		// Should not contain .min.min.js.
		$this->assertStringNotContainsString('.min.min.js', $result);
	}

	/**
	 * Test wu_get_asset with custom base dir.
	 */
	public function test_wu_get_asset_custom_base_dir(): void {

		$result = wu_get_asset('logo.png', 'img', 'static');

		$this->assertStringContainsString('static/img/', $result);
	}
}
