<?php
/**
 * Tests for scheduler functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for scheduler functions.
 */
class Scheduler_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_next_queue_run returns integer.
	 */
	public function test_get_next_queue_run(): void {

		$result = wu_get_next_queue_run();

		$this->assertIsInt($result);
	}

	/**
	 * Test wu_enqueue_async_action returns an action ID.
	 */
	public function test_enqueue_async_action(): void {

		$result = wu_enqueue_async_action('wu_test_async_hook', ['test' => true], 'wu-test');

		$this->assertIsInt($result);
		$this->assertGreaterThan(0, $result);
	}

	/**
	 * Test wu_schedule_single_action returns an action ID.
	 */
	public function test_schedule_single_action(): void {

		$result = wu_schedule_single_action(time() + 3600, 'wu_test_single_hook', ['test' => true], 'wu-test');

		$this->assertIsInt($result);
		$this->assertGreaterThan(0, $result);
	}

	/**
	 * Test wu_schedule_recurring_action returns an action ID.
	 */
	public function test_schedule_recurring_action(): void {

		$result = wu_schedule_recurring_action(time() + 3600, 86400, 'wu_test_recurring_hook', ['test' => true], 'wu-test');

		$this->assertIsInt($result);
		$this->assertGreaterThan(0, $result);
	}

	/**
	 * Test wu_next_scheduled_action returns false when no action scheduled.
	 */
	public function test_next_scheduled_action_not_found(): void {

		$result = wu_next_scheduled_action('wu_nonexistent_hook_xyz');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_scheduled_actions returns array.
	 */
	public function test_get_scheduled_actions(): void {

		$result = wu_get_scheduled_actions([
			'hook'   => 'wu_nonexistent_hook_xyz',
			'status' => 'pending',
		]);

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_unschedule_action with nonexistent hook.
	 */
	public function test_unschedule_action_nonexistent(): void {

		$result = wu_unschedule_action('wu_nonexistent_hook_xyz');

		$this->assertNull($result);
	}
}
