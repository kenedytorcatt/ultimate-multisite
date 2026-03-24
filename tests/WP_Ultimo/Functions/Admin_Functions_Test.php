<?php
/**
 * Tests for admin panel functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for admin panel functions.
 */
class Admin_Functions_Test extends WP_UnitTestCase {

	/**
	 * Load admin functions file if not already loaded.
	 */
	public function set_up(): void {

		parent::set_up();

		require_once wu_path('inc/functions/admin.php');
	}

	/**
	 * Test wu_render_empty_state outputs HTML.
	 */
	public function test_wu_render_empty_state_outputs_html(): void {

		ob_start();

		\wu_render_empty_state([
			'message'     => 'Nothing here',
			'sub_message' => 'Try again later',
		]);

		$output = ob_get_clean();

		$this->assertIsString($output);
		$this->assertNotEmpty($output);
	}

	/**
	 * Test wu_wrap_use_container outputs string.
	 */
	public function test_wu_wrap_use_container(): void {

		ob_start();

		\wu_wrap_use_container();

		$output = ob_get_clean();

		$this->assertIsString($output);
	}

	/**
	 * Test wu_responsive_table_row outputs HTML.
	 */
	public function test_wu_responsive_table_row_outputs_html(): void {

		ob_start();

		\wu_responsive_table_row(
			[
				'id'     => 'test-row',
				'title'  => 'Test Row',
				'url'    => '#',
				'status' => 'active',
				'image'  => '',
			],
			[],
			[]
		);

		$output = ob_get_clean();

		$this->assertIsString($output);
	}
}
