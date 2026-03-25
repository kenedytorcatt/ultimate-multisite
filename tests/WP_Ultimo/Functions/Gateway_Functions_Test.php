<?php
/**
 * Tests for gateway functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for gateway functions.
 */
class Gateway_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_gateways returns array.
	 */
	public function test_get_gateways_returns_array(): void {

		$result = wu_get_gateways();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_active_gateways returns array.
	 */
	public function test_get_active_gateways_returns_array(): void {

		$result = wu_get_active_gateways();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_gateway returns false for nonexistent.
	 */
	public function test_get_gateway_nonexistent(): void {

		$result = wu_get_gateway('nonexistent_gateway');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_gateway_as_options returns array.
	 */
	public function test_get_gateway_as_options_returns_array(): void {

		$result = wu_get_gateway_as_options();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_active_gateway_as_options returns array.
	 */
	public function test_get_active_gateway_as_options_returns_array(): void {

		$result = wu_get_active_gateway_as_options();

		$this->assertIsArray($result);
	}
}
