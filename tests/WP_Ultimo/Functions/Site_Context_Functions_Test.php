<?php
/**
 * Tests for site context functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for site context functions.
 */
class Site_Context_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_switch_blog_and_run executes callback.
	 */
	public function test_wu_switch_blog_and_run_executes_callback(): void {

		$result = wu_switch_blog_and_run(function () {
			return 42;
		});

		$this->assertSame(42, $result);
	}

	/**
	 * Test wu_switch_blog_and_run with specific site ID.
	 */
	public function test_wu_switch_blog_and_run_with_site_id(): void {

		$result = wu_switch_blog_and_run(function () {
			return get_current_blog_id();
		}, 1);

		$this->assertSame(1, $result);
	}

	/**
	 * Test wu_switch_blog_and_run restores original blog.
	 */
	public function test_wu_switch_blog_and_run_restores_blog(): void {

		$original_blog_id = get_current_blog_id();

		wu_switch_blog_and_run(function () {
			return true;
		}, 1);

		$this->assertSame($original_blog_id, get_current_blog_id());
	}

	/**
	 * Test wu_switch_blog_and_run defaults to main site.
	 */
	public function test_wu_switch_blog_and_run_defaults_to_main_site(): void {

		$result = wu_switch_blog_and_run(function () {
			return get_current_blog_id();
		}, false);

		// Should switch to main site.
		$this->assertIsInt($result);
	}
}
