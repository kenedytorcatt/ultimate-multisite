<?php
/**
 * Tests for element functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for element functions.
 */
class Element_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_element_setup_preview fires action.
	 */
	public function test_wu_element_setup_preview_fires_action(): void {

		$fired = false;

		add_action(
			'wu_element_preview',
			function () use (&$fired) {
				$fired = true;
			}
		);

		wu_element_setup_preview();

		$this->assertTrue($fired);
	}

	/**
	 * Test wu_element_setup_preview does not fire twice.
	 */
	public function test_wu_element_setup_preview_does_not_fire_twice(): void {

		$count = 0;

		add_action(
			'wu_element_preview',
			function () use (&$count) {
				$count++;
			}
		);

		// First call fires the action.
		wu_element_setup_preview();

		$first_count = $count;

		// Second call should not fire again (did_action check).
		wu_element_setup_preview();

		$this->assertSame($first_count, $count);
	}
}
