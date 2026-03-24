<?php
/**
 * Tests for geolocation functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for geolocation functions.
 */
class Geolocation_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_ip returns a string.
	 */
	public function test_wu_get_ip_returns_string(): void {

		$ip = wu_get_ip();

		$this->assertIsString($ip);
	}

	/**
	 * Test wu_get_ip filter works.
	 */
	public function test_wu_get_ip_filter(): void {

		add_filter(
			'wu_get_ip',
			function () {
				return '192.168.1.100';
			}
		);

		$ip = wu_get_ip();

		$this->assertSame('192.168.1.100', $ip);

		remove_all_filters('wu_get_ip');
	}
}
