<?php
/**
 * Tests for reflection functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for reflection functions.
 */
class Reflection_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_reflection_parse_arguments_from_setters returns array.
	 */
	public function test_parse_arguments_from_setters_returns_array(): void {

		$result = wu_reflection_parse_arguments_from_setters('\WP_Ultimo\Models\Product');

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
	}

	/**
	 * Test wu_reflection_parse_arguments_from_setters with schema.
	 */
	public function test_parse_arguments_from_setters_with_schema(): void {

		$result = wu_reflection_parse_arguments_from_setters('\WP_Ultimo\Models\Product', true);

		$this->assertIsArray($result);

		if ( ! empty($result)) {
			$first = $result[0];
			$this->assertArrayHasKey('name', $first);
			$this->assertArrayHasKey('type', $first);
		}
	}

	/**
	 * Test wu_reflection_parse_arguments_from_setters without schema.
	 */
	public function test_parse_arguments_from_setters_without_schema(): void {

		$result = wu_reflection_parse_arguments_from_setters('\WP_Ultimo\Models\Product', false);

		$this->assertIsArray($result);

		if ( ! empty($result)) {
			$this->assertIsString($result[0]);
		}
	}

	/**
	 * Test wu_reflection_parse_arguments_from_setters with Customer.
	 */
	public function test_parse_arguments_from_setters_customer(): void {

		$result = wu_reflection_parse_arguments_from_setters('\WP_Ultimo\Models\Customer', false);

		$this->assertIsArray($result);
		$this->assertContains('email_verification', $result);
	}

	/**
	 * Test wu_reflection_parse_arguments_from_setters with Membership.
	 */
	public function test_parse_arguments_from_setters_membership(): void {

		$result = wu_reflection_parse_arguments_from_setters('\WP_Ultimo\Models\Membership', false);

		$this->assertIsArray($result);
		$this->assertContains('status', $result);
		$this->assertContains('amount', $result);
	}

	/**
	 * Test wu_reflection_parse_object_arguments returns array.
	 */
	public function test_parse_object_arguments_returns_array(): void {

		$result = wu_reflection_parse_object_arguments('\WP_Ultimo\Models\Product');

		$this->assertIsArray($result);
	}
}
