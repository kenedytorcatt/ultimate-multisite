<?php
/**
 * Test case for Hooks.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests;

use WP_Ultimo\Hooks;

/**
 * Test Hooks functionality.
 */
class Hooks_Test extends \WP_UnitTestCase {

	/**
	 * Test on_activation sets network option.
	 */
	public function test_on_activation_sets_option(): void {

		// Clear any existing value
		delete_network_option(null, 'wu_activation');

		Hooks::on_activation();

		$value = get_network_option(null, 'wu_activation');

		$this->assertEquals('yes', $value);

		// Clean up
		delete_network_option(null, 'wu_activation');
	}

	/**
	 * Test on_deactivation fires wu_deactivation action.
	 */
	public function test_on_deactivation_fires_action(): void {

		$fired = false;

		add_action('wu_deactivation', function () use (&$fired) {
			$fired = true;
		});

		Hooks::on_deactivation();

		$this->assertTrue($fired);
	}

	/**
	 * Test on_activation_do clears activation flag.
	 */
	public function test_on_activation_do_clears_flag(): void {

		// Set the activation flag
		update_network_option(null, 'wu_activation', 'yes');

		// Simulate the activate request parameter
		$_REQUEST['activate'] = true;

		Hooks::on_activation_do();

		$value = get_network_option(null, 'wu_activation', false);

		// Flag should be cleared
		$this->assertFalse($value);

		// Clean up
		unset($_REQUEST['activate']);
	}

	/**
	 * Test on_activation_do does nothing without activate param.
	 */
	public function test_on_activation_do_without_activate_param(): void {

		update_network_option(null, 'wu_activation', 'yes');

		// Don't set $_REQUEST['activate']
		unset($_REQUEST['activate']);

		Hooks::on_activation_do();

		// Flag should still be set since activate param wasn't present
		$value = get_network_option(null, 'wu_activation', false);

		$this->assertEquals('yes', $value);

		// Clean up
		delete_network_option(null, 'wu_activation');
	}

	/**
	 * Test on_activation_do fires wu_activation action.
	 */
	public function test_on_activation_do_fires_action(): void {

		update_network_option(null, 'wu_activation', 'yes');
		$_REQUEST['activate'] = true;

		$fired = false;

		add_action('wu_activation', function () use (&$fired) {
			$fired = true;
		});

		Hooks::on_activation_do();

		$this->assertTrue($fired);

		// Clean up
		unset($_REQUEST['activate']);
		delete_network_option(null, 'wu_activation');
	}
}
