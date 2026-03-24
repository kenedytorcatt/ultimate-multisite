<?php
/**
 * Tests for documentation functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for documentation functions.
 */
class Documentation_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_documentation_url returns string.
	 */
	public function test_wu_get_documentation_url_returns_string(): void {

		$result = wu_get_documentation_url('some-slug');

		$this->assertIsString($result);
	}

	/**
	 * Test wu_get_documentation_url with return_default false.
	 */
	public function test_wu_get_documentation_url_no_default(): void {

		$result = wu_get_documentation_url('nonexistent-slug', false);

		// Returns false when slug not found and return_default is false.
		$this->assertFalse($result);
	}
}
