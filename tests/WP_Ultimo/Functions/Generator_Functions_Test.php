<?php
/**
 * Tests for generator functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for generator functions.
 */
class Generator_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_generate_csv with empty data does nothing.
	 */
	public function test_wu_generate_csv_empty_data(): void {

		ob_start();

		wu_generate_csv('test', []);

		$output = ob_get_clean();

		// Empty data should produce no output.
		$this->assertEmpty($output);
	}

	/**
	 * Test wu_generate_csv with array data produces CSV output.
	 */
	public function test_wu_generate_csv_with_array_data(): void {

		ob_start();

		// Suppress header warnings since output already started in test env.
		@wu_generate_csv('test', [
			['Name', 'Email'],
			['John', 'john@example.com'],
		]);

		$output = ob_get_clean();

		$this->assertStringContainsString('Name', $output);
		$this->assertStringContainsString('john@example.com', $output);
	}

	/**
	 * Test wu_generate_csv with object data produces CSV output.
	 */
	public function test_wu_generate_csv_with_object_data(): void {

		$obj        = new \stdClass();
		$obj->name  = 'Jane';
		$obj->email = 'jane@example.com';

		ob_start();

		// Suppress header warnings since output already started in test env.
		@wu_generate_csv('test', [$obj]);

		$output = ob_get_clean();

		$this->assertStringContainsString('Jane', $output);
		$this->assertStringContainsString('jane@example.com', $output);
	}
}
