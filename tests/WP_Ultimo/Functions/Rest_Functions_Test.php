<?php
/**
 * Tests for REST API functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for REST API functions.
 */
class Rest_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_rest_get_endpoint_from_class_name with valid class.
	 */
	public function test_get_endpoint_from_class_name_valid(): void {

		$result = wu_rest_get_endpoint_from_class_name('\WP_Ultimo\Models\Product');

		$this->assertEquals('product', $result);
	}

	/**
	 * Test wu_rest_get_endpoint_from_class_name with membership.
	 */
	public function test_get_endpoint_from_class_name_membership(): void {

		$result = wu_rest_get_endpoint_from_class_name('\WP_Ultimo\Models\Membership');

		$this->assertEquals('membership', $result);
	}

	/**
	 * Test wu_rest_get_endpoint_from_class_name with customer.
	 */
	public function test_get_endpoint_from_class_name_customer(): void {

		$result = wu_rest_get_endpoint_from_class_name('\WP_Ultimo\Models\Customer');

		$this->assertEquals('customer', $result);
	}

	/**
	 * Test wu_rest_get_endpoint_from_class_name with nonexistent class.
	 */
	public function test_get_endpoint_from_class_name_nonexistent(): void {

		$result = wu_rest_get_endpoint_from_class_name('NonExistentClass');

		$this->assertEquals('NonExistentClass', $result);
	}

	/**
	 * Test wu_rest_treat_argument_type converts bool.
	 */
	public function test_treat_argument_type_bool(): void {

		$this->assertEquals('boolean', wu_rest_treat_argument_type('bool'));
	}

	/**
	 * Test wu_rest_treat_argument_type converts int.
	 */
	public function test_treat_argument_type_int(): void {

		$this->assertEquals('integer', wu_rest_treat_argument_type('int'));
	}

	/**
	 * Test wu_rest_treat_argument_type converts float.
	 */
	public function test_treat_argument_type_float(): void {

		$this->assertEquals('number', wu_rest_treat_argument_type('float'));
	}

	/**
	 * Test wu_rest_treat_argument_type passes through string.
	 */
	public function test_treat_argument_type_string(): void {

		$this->assertEquals('string', wu_rest_treat_argument_type('string'));
	}

	/**
	 * Test wu_rest_treat_argument_type passes through array.
	 */
	public function test_treat_argument_type_array(): void {

		$this->assertEquals('array', wu_rest_treat_argument_type('array'));
	}

	/**
	 * Test wu_rest_get_endpoint_schema returns array.
	 */
	public function test_get_endpoint_schema_returns_array(): void {

		$result = wu_rest_get_endpoint_schema('\WP_Ultimo\Models\Product');

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_rest_get_endpoint_schema with update context.
	 */
	public function test_get_endpoint_schema_update_context(): void {

		$result = wu_rest_get_endpoint_schema('\WP_Ultimo\Models\Product', 'update');

		$this->assertIsArray($result);
	}
}
