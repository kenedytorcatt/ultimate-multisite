<?php
/**
 * Tests for debug functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for debug functions.
 */
class Debug_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_try_unlimited_server_limits runs without error.
	 */
	public function test_wu_try_unlimited_server_limits(): void {

		wu_try_unlimited_server_limits();

		// If we get here without error, the function works.
		$this->assertTrue(true);
	}

	/**
	 * Test wu_setup_memory_limit_trap runs without error.
	 */
	public function test_wu_setup_memory_limit_trap(): void {

		wu_setup_memory_limit_trap('plain');

		$this->assertTrue(true);
	}

	/**
	 * Test wu_setup_memory_limit_trap with json return type.
	 */
	public function test_wu_setup_memory_limit_trap_json(): void {

		wu_setup_memory_limit_trap('json');

		$this->assertTrue(true);
	}
}
