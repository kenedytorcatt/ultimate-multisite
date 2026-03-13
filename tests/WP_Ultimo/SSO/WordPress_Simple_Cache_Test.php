<?php
/**
 * Unit tests for WordPress_Simple_Cache class.
 *
 * Tests the PSR-16 cache implementation using WordPress transients.
 *
 * @package WP_Ultimo
 * @subpackage Tests\SSO
 */

namespace WP_Ultimo\SSO;

use Psr\SimpleCache\CacheInterface;

/**
 * WordPress_Simple_Cache Test Class
 */
class WordPress_Simple_Cache_Test extends \WP_UnitTestCase {

	/**
	 * Cache instance for testing.
	 *
	 * @var WordPress_Simple_Cache
	 */
	protected $cache;

	/**
	 * Test cache prefix.
	 *
	 * @var string
	 */
	protected $prefix = 'wu_test_cache_';

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->cache = new WordPress_Simple_Cache($this->prefix);

		// Clean up any existing test cache entries.
		$this->cache->clear();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Clean up test cache entries.
		if ($this->cache) {
			$this->cache->clear();
		}

		parent::tearDown();
	}

	/**
	 * Test that the cache implements PSR-16 CacheInterface.
	 */
	public function test_implements_psr16_cache_interface(): void {
		$this->assertInstanceOf(CacheInterface::class, $this->cache);
	}

	/**
	 * Test basic get/set operations.
	 */
	public function test_get_and_set(): void {
		$key   = 'test_key';
		$value = 'test_value';

		// Set a value.
		$result = $this->cache->set($key, $value);
		$this->assertTrue($result, 'set() should return true on success');

		// Get the value.
		$retrieved = $this->cache->get($key);
		$this->assertSame($value, $retrieved, 'Retrieved value should match set value');
	}

	/**
	 * Test get with default value when key doesn't exist.
	 */
	public function test_get_with_default_value(): void {
		$key     = 'nonexistent_key';
		$default = 'default_value';

		$result = $this->cache->get($key, $default);
		$this->assertSame($default, $result, 'Should return default value for nonexistent key');
	}

	/**
	 * Test delete operation.
	 */
	public function test_delete(): void {
		$key   = 'test_delete_key';
		$value = 'test_value';

		// Set and verify.
		$this->cache->set($key, $value);
		$this->assertSame($value, $this->cache->get($key));

		// Delete and verify.
		$result = $this->cache->delete($key);
		$this->assertTrue($result, 'delete() should return true on success');

		// Verify it's gone.
		$this->assertNull($this->cache->get($key), 'Deleted key should return null');
	}

	/**
	 * Test has() method.
	 */
	public function test_has(): void {
		$key   = 'test_has_key';
		$value = 'test_value';

		// Key shouldn't exist initially.
		$this->assertFalse($this->cache->has($key), 'has() should return false for nonexistent key');

		// Set value.
		$this->cache->set($key, $value);

		// Now it should exist.
		$this->assertTrue($this->cache->has($key), 'has() should return true for existing key');
	}

	/**
	 * Test clear operation.
	 */
	public function test_clear(): void {
		// Set multiple values.
		$this->cache->set('key1', 'value1');
		$this->cache->set('key2', 'value2');
		$this->cache->set('key3', 'value3');

		// Verify they exist.
		$this->assertTrue($this->cache->has('key1'));
		$this->assertTrue($this->cache->has('key2'));
		$this->assertTrue($this->cache->has('key3'));

		// Clear cache.
		$result = $this->cache->clear();
		$this->assertTrue($result, 'clear() should return true on success');

		// Verify all are gone.
		$this->assertFalse($this->cache->has('key1'));
		$this->assertFalse($this->cache->has('key2'));
		$this->assertFalse($this->cache->has('key3'));
	}

	/**
	 * Test getMultiple operation.
	 */
	public function test_get_multiple(): void {
		// Set multiple values.
		$this->cache->set('key1', 'value1');
		$this->cache->set('key2', 'value2');
		$this->cache->set('key3', 'value3');

		// Get multiple.
		$keys   = array('key1', 'key2', 'nonexistent');
		$values = $this->cache->getMultiple($keys, 'default');

		$this->assertIsArray($values);
		$this->assertSame('value1', $values['key1']);
		$this->assertSame('value2', $values['key2']);
		$this->assertSame('default', $values['nonexistent']);
	}

	/**
	 * Test setMultiple operation.
	 */
	public function test_set_multiple(): void {
		$values = array(
			'multi1' => 'value1',
			'multi2' => 'value2',
			'multi3' => 'value3',
		);

		// Set multiple.
		$result = $this->cache->setMultiple($values);
		$this->assertTrue($result, 'setMultiple() should return true on success');

		// Verify all were set.
		$this->assertSame('value1', $this->cache->get('multi1'));
		$this->assertSame('value2', $this->cache->get('multi2'));
		$this->assertSame('value3', $this->cache->get('multi3'));
	}

	/**
	 * Test deleteMultiple operation.
	 */
	public function test_delete_multiple(): void {
		// Set multiple values.
		$this->cache->set('del1', 'value1');
		$this->cache->set('del2', 'value2');
		$this->cache->set('del3', 'value3');

		// Delete multiple.
		$keys   = array('del1', 'del2');
		$result = $this->cache->deleteMultiple($keys);
		$this->assertTrue($result, 'deleteMultiple() should return true on success');

		// Verify deleted keys are gone.
		$this->assertNull($this->cache->get('del1'));
		$this->assertNull($this->cache->get('del2'));

		// Verify remaining key still exists.
		$this->assertSame('value3', $this->cache->get('del3'));
	}

	/**
	 * Test TTL with integer seconds.
	 */
	public function test_ttl_with_integer_seconds(): void {
		$key   = 'ttl_test_key';
		$value = 'ttl_test_value';
		$ttl   = 60; // 60 seconds.

		$result = $this->cache->set($key, $value, $ttl);
		$this->assertTrue($result);

		// Value should be retrievable.
		$retrieved = $this->cache->get($key);
		$this->assertSame($value, $retrieved);

		// Note: We can't easily test expiration in unit tests without time manipulation.
		// The transient is set with the correct expiration time.
	}

	/**
	 * Test TTL with null (no expiration).
	 */
	public function test_ttl_with_null(): void {
		$key   = 'no_ttl_key';
		$value = 'no_ttl_value';

		$result = $this->cache->set($key, $value, null);
		$this->assertTrue($result);

		// Value should be retrievable.
		$retrieved = $this->cache->get($key);
		$this->assertSame($value, $retrieved);
	}

	/**
	 * Test TTL with DateInterval.
	 */
	public function test_ttl_with_date_interval(): void {
		$key      = 'dateinterval_key';
		$value    = 'dateinterval_value';
		$interval = new \DateInterval('PT1H'); // 1 hour.

		$result = $this->cache->set($key, $value, $interval);
		$this->assertTrue($result);

		// Value should be retrievable.
		$retrieved = $this->cache->get($key);
		$this->assertSame($value, $retrieved);
	}

	/**
	 * Test storing different data types.
	 */
	public function test_stores_different_data_types(): void {
		// String.
		$this->cache->set('string', 'test string');
		$this->assertSame('test string', $this->cache->get('string'));

		// Integer.
		$this->cache->set('integer', 42);
		$this->assertSame(42, $this->cache->get('integer'));

		// Float.
		$this->cache->set('float', 3.14);
		$this->assertSame(3.14, $this->cache->get('float'));

		// Boolean.
		$this->cache->set('bool_true', true);
		$this->assertTrue($this->cache->get('bool_true'));

		$this->cache->set('bool_false', false);
		$this->assertFalse($this->cache->get('bool_false'));

		// Array.
		$array = array('foo' => 'bar', 'baz' => 123);
		$this->cache->set('array', $array);
		$this->assertSame($array, $this->cache->get('array'));

		// Object.
		$object       = new \stdClass();
		$object->prop = 'value';
		$this->cache->set('object', $object);
		$retrieved = $this->cache->get('object');
		$this->assertInstanceOf(\stdClass::class, $retrieved);
		$this->assertSame('value', $retrieved->prop);
	}

	/**
	 * Test cache prefix isolation.
	 */
	public function test_cache_prefix_isolation(): void {
		$cache1 = new WordPress_Simple_Cache('prefix1_');
		$cache2 = new WordPress_Simple_Cache('prefix2_');

		// Set same key in both caches with different values.
		$cache1->set('test', 'value1');
		$cache2->set('test', 'value2');

		// Each should retrieve its own value.
		$this->assertSame('value1', $cache1->get('test'));
		$this->assertSame('value2', $cache2->get('test'));

		// Clear one cache shouldn't affect the other.
		$cache1->clear();
		$this->assertNull($cache1->get('test'));
		$this->assertSame('value2', $cache2->get('test'));

		// Cleanup.
		$cache2->clear();
	}

	/**
	 * Test that cache works with SSO integration.
	 */
	public function test_cache_integration_with_sso(): void {
		// Create cache instance with SSO prefix.
		$sso_cache = new WordPress_Simple_Cache('wu_sso_');

		// Simulate SSO session data.
		$broker_id  = 'test_broker_123';
		$session_id = 'session_abc_456';
		$user_id    = 42;

		// Store session mapping (broker + token -> session_id).
		$cache_key = $broker_id . '_' . 'test_token';
		$sso_cache->set($cache_key, $session_id, 3600); // 1 hour TTL.

		// Verify retrieval.
		$retrieved = $sso_cache->get($cache_key);
		$this->assertSame($session_id, $retrieved);

		// Simulate cleanup.
		$sso_cache->delete($cache_key);
		$this->assertNull($sso_cache->get($cache_key));
	}

	/**
	 * Test compatibility with jasny/sso CacheInterface usage.
	 */
	public function test_jasny_sso_compatibility(): void {
		// Jasny SSO uses PSR-16 CacheInterface methods.
		$cache = new WordPress_Simple_Cache('jasny_test_');

		// Simulate broker token storage.
		$broker_id = 'broker_123';
		$token     = 'token_abc';
		$key       = 'sso_' . $broker_id . '_' . $token;
		$value     = 'session_xyz';
		$ttl       = 180; // 3 minutes, typical for SSO.

		// Store.
		$result = $cache->set($key, $value, $ttl);
		$this->assertTrue($result);

		// Retrieve.
		$retrieved = $cache->get($key);
		$this->assertSame($value, $retrieved);

		// Verify has().
		$this->assertTrue($cache->has($key));

		// Delete.
		$cache->delete($key);
		$this->assertFalse($cache->has($key));

		// Cleanup.
		$cache->clear();
	}
}
