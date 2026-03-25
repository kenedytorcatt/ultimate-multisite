<?php

namespace WP_Ultimo\Limitations;

/**
 * Tests for the Limit abstract class.
 * We test with a concrete implementation.
 */
class Limit_Test extends \WP_UnitTestCase {

	/**
	 * Create a concrete Limit implementation for testing.
	 *
	 * @param array $data The limit data.
	 * @return Limit
	 */
	private function create_test_limit($data = []) {

		return new class($data) extends Limit {

			protected $id = 'test_limit';

			public function check($value_to_check, $limit, $type = '') {

				if (is_numeric($limit)) {
					return $value_to_check <= $limit;
				}

				return true;
			}
		};
	}

	/**
	 * Test constructor sets up data correctly.
	 */
	public function test_constructor() {

		$limit = $this->create_test_limit([
			'limit'   => 100,
			'enabled' => true,
		]);

		$this->assertSame('test_limit', $limit->get_id());
		$this->assertSame(100, $limit->get_limit());
		$this->assertTrue($limit->is_enabled());
	}

	/**
	 * Test setup with default values.
	 */
	public function test_setup_defaults() {

		$limit = $this->create_test_limit([]);

		$this->assertNull($limit->get_limit());
		$this->assertTrue($limit->is_enabled()); // Default is true
	}

	/**
	 * Test setup with explicit enabled=false.
	 */
	public function test_setup_disabled() {

		$limit = $this->create_test_limit([
			'enabled' => false,
		]);

		$this->assertFalse($limit->is_enabled());
	}

	/**
	 * Test has_own_limit returns true when limit is set.
	 */
	public function test_has_own_limit_true() {

		$limit = $this->create_test_limit([
			'limit' => 50,
		]);

		$this->assertTrue($limit->has_own_limit());
	}

	/**
	 * Test has_own_limit returns false when limit is not set.
	 */
	public function test_has_own_limit_false() {

		$limit = $this->create_test_limit([]);

		$this->assertFalse($limit->has_own_limit());
	}

	/**
	 * Test has_own_enabled returns true when enabled is explicitly set.
	 */
	public function test_has_own_enabled_true() {

		$limit = $this->create_test_limit([
			'enabled' => false,
		]);

		$this->assertTrue($limit->has_own_enabled());
	}

	/**
	 * Test has_own_enabled returns false when enabled uses default.
	 */
	public function test_has_own_enabled_false() {

		$limit = $this->create_test_limit([]);

		$this->assertFalse($limit->has_own_enabled());
	}

	/**
	 * Test allowed returns true when under limit.
	 */
	public function test_allowed_under_limit() {

		$limit = $this->create_test_limit([
			'limit'   => 100,
			'enabled' => true,
		]);

		$this->assertTrue($limit->allowed(50));
	}

	/**
	 * Test allowed returns true at exact limit.
	 */
	public function test_allowed_at_limit() {

		$limit = $this->create_test_limit([
			'limit'   => 100,
			'enabled' => true,
		]);

		$this->assertTrue($limit->allowed(100));
	}

	/**
	 * Test allowed returns false when over limit.
	 */
	public function test_allowed_over_limit() {

		$limit = $this->create_test_limit([
			'limit'   => 100,
			'enabled' => true,
		]);

		$this->assertFalse($limit->allowed(150));
	}

	/**
	 * Test allowed returns true when disabled.
	 */
	public function test_allowed_when_disabled() {

		$limit = $this->create_test_limit([
			'limit'   => 100,
			'enabled' => false,
		]);

		// When disabled, allowed() returns the enabled status (false)
		$this->assertFalse($limit->allowed(150));
	}

	/**
	 * Test to_array returns correct structure.
	 */
	public function test_to_array() {

		$limit = $this->create_test_limit([
			'limit'   => 75,
			'enabled' => true,
		]);

		$array = $limit->to_array();

		$this->assertIsArray($array);
		$this->assertArrayHasKey('id', $array);
		$this->assertArrayHasKey('limit', $array);
		$this->assertArrayHasKey('enabled', $array);
		$this->assertSame('test_limit', $array['id']);
		$this->assertSame(75, $array['limit']);
		$this->assertTrue($array['enabled']);
	}

	/**
	 * Test to_array excludes private properties.
	 */
	public function test_to_array_excludes_private() {

		$limit = $this->create_test_limit([]);

		$array = $limit->to_array();

		$this->assertArrayNotHasKey('has_own_limit', $array);
		$this->assertArrayNotHasKey('has_own_enabled', $array);
		$this->assertArrayNotHasKey('enabled_default_value', $array);
	}

	/**
	 * Test default_state returns correct structure.
	 */
	public function test_default_state() {

		$state = Limit::default_state();

		$this->assertIsArray($state);
		$this->assertArrayHasKey('enabled', $state);
		$this->assertArrayHasKey('limit', $state);
		$this->assertFalse($state['enabled']);
		$this->assertNull($state['limit']);
	}

	/**
	 * Test jsonSerialize returns JSON string.
	 */
	public function test_json_serialize() {

		$limit = $this->create_test_limit([
			'limit'   => 50,
			'enabled' => true,
		]);

		$json = $limit->jsonSerialize();

		$this->assertIsString($json);

		$decoded = json_decode($json, true);

		$this->assertIsArray($decoded);
		$this->assertSame('test_limit', $decoded['id']);
	}

	/**
	 * Test allowed with type parameter.
	 */
	public function test_allowed_with_type() {

		$limit = $this->create_test_limit([
			'limit'   => 10,
			'enabled' => true,
		]);

		$this->assertTrue($limit->allowed(5, 'some_type'));
		$this->assertFalse($limit->allowed(15, 'some_type'));
	}

	/**
	 * Test setup with non-array data.
	 */
	public function test_setup_with_non_array() {

		$limit = $this->create_test_limit('invalid');

		// Should handle gracefully and use defaults
		$this->assertNull($limit->get_limit());
		$this->assertTrue($limit->is_enabled());
	}

	/**
	 * Test setup with object limit.
	 */
	public function test_setup_with_object_limit() {

		$limit = $this->create_test_limit([
			'limit' => (object) ['value' => 100],
		]);

		// Limit should be converted to object
		$this->assertIsObject($limit->get_limit());
	}

	/**
	 * Test handle_enabled method exists.
	 */
	public function test_handle_enabled_exists() {

		$limit = $this->create_test_limit([]);

		$this->assertTrue(method_exists($limit, 'handle_enabled'));
	}

	/**
	 * Test handle_limit method exists.
	 */
	public function test_handle_limit_exists() {

		$limit = $this->create_test_limit([]);

		$this->assertTrue(method_exists($limit, 'handle_limit'));
	}

	/**
	 * Test handle_others method.
	 */
	public function test_handle_others() {

		$limit = $this->create_test_limit([]);

		$module = ['key' => 'value'];
		$result = $limit->handle_others($module);

		// Default implementation returns module unchanged
		$this->assertSame($module, $result);
	}

	/**
	 * Test allowed applies filter.
	 */
	public function test_allowed_applies_filter() {

		$limit = $this->create_test_limit([
			'limit'   => 100,
			'enabled' => true,
		]);

		add_filter('wu_limit_test_limit__allowed', '__return_true');

		// Even though value > limit, filter should force true
		$this->assertTrue($limit->allowed(200));

		remove_filter('wu_limit_test_limit__allowed', '__return_true');
	}

	/**
	 * Test is_enabled with type parameter.
	 */
	public function test_is_enabled_with_type() {

		$limit = $this->create_test_limit([
			'enabled' => true,
		]);

		$this->assertTrue($limit->is_enabled('any_type'));
	}

	/**
	 * Test get_limit with type parameter.
	 */
	public function test_get_limit_with_type() {

		$limit = $this->create_test_limit([
			'limit' => 75,
		]);

		$this->assertSame(75, $limit->get_limit('any_type'));
	}
}
