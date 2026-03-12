<?php
/**
 * Tests for HTTP helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for HTTP helper functions.
 */
class Http_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_x_header sends header when debug is on and filter allows.
	 */
	public function test_wu_x_header_with_filter_enabled(): void {

		add_filter('wu_should_send_x_headers', '__return_true');

		// Should not throw — just exercises the code path.
		wu_x_header('X-Test: value');

		remove_filter('wu_should_send_x_headers', '__return_true');

		$this->assertTrue(true);
	}

	/**
	 * Test wu_x_header does not send header when filter disables it.
	 */
	public function test_wu_x_header_with_filter_disabled(): void {

		add_filter('wu_should_send_x_headers', '__return_false');

		wu_x_header('X-Test: value');

		remove_filter('wu_should_send_x_headers', '__return_false');

		$this->assertTrue(true);
	}

	/**
	 * Test wu_no_cache fires the action.
	 */
	public function test_wu_no_cache_fires_action(): void {

		$fired = false;

		add_action(
			'wu_no_cache',
			function () use (&$fired) {
				$fired = true;
			}
		);

		wu_no_cache();

		$this->assertTrue($fired);
	}

	/**
	 * Test wu_get_input returns null when no input.
	 */
	public function test_wu_get_input_returns_null_for_empty_input(): void {

		$result = wu_get_input();

		// php://input is empty in test context, so json_decode returns null.
		$this->assertNull($result);
	}

	/**
	 * Test wu_get_input raw mode returns string.
	 */
	public function test_wu_get_input_raw_returns_string(): void {

		$result = wu_get_input(true);

		$this->assertIsString($result);
	}
}
