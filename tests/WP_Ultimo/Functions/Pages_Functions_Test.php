<?php
/**
 * Tests for pages functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for pages functions.
 */
class Pages_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_is_registration_page returns bool.
	 */
	public function test_is_registration_page_returns_bool(): void {

		$result = wu_is_registration_page();

		$this->assertIsBool($result);
	}

	/**
	 * Test wu_is_registration_page returns false on subsite.
	 */
	public function test_is_registration_page_false_on_subsite(): void {

		// On main site in test env, but no post context.
		$result = wu_is_registration_page();

		$this->assertFalse($result);
	}

	/**
	 * Test wu_is_update_page returns bool.
	 */
	public function test_is_update_page_returns_bool(): void {

		$result = wu_is_update_page();

		$this->assertIsBool($result);
	}

	/**
	 * Test wu_is_update_page returns false without post.
	 */
	public function test_is_update_page_false_without_post(): void {

		$result = wu_is_update_page();

		$this->assertFalse($result);
	}

	/**
	 * Test wu_is_new_site_page returns bool.
	 */
	public function test_is_new_site_page_returns_bool(): void {

		$result = wu_is_new_site_page();

		$this->assertIsBool($result);
	}

	/**
	 * Test wu_is_new_site_page returns false without post.
	 */
	public function test_is_new_site_page_false_without_post(): void {

		$result = wu_is_new_site_page();

		$this->assertFalse($result);
	}

	/**
	 * Test wu_is_login_page returns bool.
	 */
	public function test_is_login_page_returns_bool(): void {

		$result = wu_is_login_page();

		$this->assertIsBool($result);
	}
}
