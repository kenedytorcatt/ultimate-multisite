<?php
/**
 * Tests for the License class.
 */

namespace WP_Ultimo;

use WP_UnitTestCase;

/**
 * @group license
 */
class License_Test extends WP_UnitTestCase {

	// ------------------------------------------------------------------
	// Singleton
	// ------------------------------------------------------------------

	public function test_get_instance_returns_singleton() {
		$instance = License::get_instance();
		$this->assertInstanceOf(License::class, $instance);
	}

	public function test_get_instance_returns_same_instance() {
		$a = License::get_instance();
		$b = License::get_instance();
		$this->assertSame($a, $b);
	}

	// ------------------------------------------------------------------
	// get_license_key
	// ------------------------------------------------------------------

	public function test_get_license_key_returns_null() {
		$instance = License::get_instance();
		$this->assertNull($instance->get_license_key());
	}
}
