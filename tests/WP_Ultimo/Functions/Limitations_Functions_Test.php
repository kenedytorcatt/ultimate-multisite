<?php
/**
 * Tests for limitations functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for limitations functions.
 */
class Limitations_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_register_limit_module adds filter.
	 */
	public function test_register_limit_module(): void {

		wu_register_limit_module('test_module', 'TestModuleClass');

		$classes = apply_filters('wu_limit_classes', []);

		$this->assertArrayHasKey('test_module', $classes);
		$this->assertEquals('TestModuleClass', $classes['test_module']);
	}

	/**
	 * Test wu_register_limit_module sanitizes id.
	 */
	public function test_register_limit_module_sanitizes_id(): void {

		wu_register_limit_module('Test Module With Spaces', 'TestClass');

		$classes = apply_filters('wu_limit_classes', []);

		$this->assertArrayHasKey('test-module-with-spaces', $classes);
	}

	/**
	 * Test wu_async_activate_plugins does not throw.
	 */
	public function test_async_activate_plugins(): void {

		// Should not throw - just enqueues an async action.
		wu_async_activate_plugins(1, ['test-plugin/test-plugin.php']);

		$this->assertTrue(true);
	}

	/**
	 * Test wu_async_deactivate_plugins does not throw.
	 */
	public function test_async_deactivate_plugins(): void {

		wu_async_deactivate_plugins(1, ['test-plugin/test-plugin.php']);

		$this->assertTrue(true);
	}

	/**
	 * Test wu_async_switch_theme does not throw.
	 */
	public function test_async_switch_theme(): void {

		wu_async_switch_theme(1, 'twentytwentyfour');

		$this->assertTrue(true);
	}
}
