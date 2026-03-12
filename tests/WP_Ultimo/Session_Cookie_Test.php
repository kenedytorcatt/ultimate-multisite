<?php
/**
 * Test case for Session_Cookie.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests;

use WP_Ultimo\Session_Cookie;

/**
 * Test Session_Cookie functionality.
 */
class Session_Cookie_Test extends \WP_UnitTestCase {

	/**
	 * Test constructor creates instance.
	 */
	public function test_constructor(): void {

		$session = new Session_Cookie('test_realm');

		$this->assertInstanceOf(Session_Cookie::class, $session);
	}

	/**
	 * Test get returns null for nonexistent key.
	 */
	public function test_get_nonexistent_key(): void {

		$session = new Session_Cookie('test_realm');

		$this->assertNull($session->get('nonexistent'));
	}

	/**
	 * Test get without key returns all data.
	 */
	public function test_get_all_data(): void {

		$session = new Session_Cookie('test_realm');

		$data = $session->get();

		$this->assertIsArray($data);
	}

	/**
	 * Test set and get.
	 */
	public function test_set_and_get(): void {

		$session = new Session_Cookie('test_realm');

		$result = $session->set('key1', 'value1');

		$this->assertTrue($result);
		$this->assertEquals('value1', $session->get('key1'));
	}

	/**
	 * Test set overwrites existing value.
	 */
	public function test_set_overwrites(): void {

		$session = new Session_Cookie('test_realm');

		$session->set('key1', 'original');
		$session->set('key1', 'updated');

		$this->assertEquals('updated', $session->get('key1'));
	}

	/**
	 * Test set with different value types.
	 */
	public function test_set_different_types(): void {

		$session = new Session_Cookie('test_realm');

		$session->set('string', 'hello');
		$session->set('int', 42);
		$session->set('bool', true);
		$session->set('array', ['a', 'b']);

		$this->assertEquals('hello', $session->get('string'));
		$this->assertEquals(42, $session->get('int'));
		$this->assertTrue($session->get('bool'));
		$this->assertEquals(['a', 'b'], $session->get('array'));
	}

	/**
	 * Test add_values appends to existing array.
	 */
	public function test_add_values(): void {

		$session = new Session_Cookie('test_realm');

		$session->set('items', ['a', 'b']);
		$result = $session->add_values('items', ['c', 'd']);

		$this->assertTrue($result);
		$this->assertEquals(['a', 'b', 'c', 'd'], $session->get('items'));
	}

	/**
	 * Test add_values creates new key if not exists.
	 */
	public function test_add_values_new_key(): void {

		$session = new Session_Cookie('test_realm');

		$session->add_values('new_key', ['x', 'y']);

		$this->assertEquals(['x', 'y'], $session->get('new_key'));
	}

	/**
	 * Test clear empties all data.
	 */
	public function test_clear(): void {

		$session = new Session_Cookie('test_realm');

		$session->set('key1', 'value1');
		$session->set('key2', 'value2');

		$session->clear();

		$this->assertNull($session->get('key1'));
		$this->assertNull($session->get('key2'));
		$this->assertEmpty($session->get());
	}

	/**
	 * Test multiple sessions with different realms are isolated.
	 */
	public function test_realm_isolation(): void {

		$session1 = new Session_Cookie('realm_a');
		$session2 = new Session_Cookie('realm_b');

		$session1->set('key', 'value_a');
		$session2->set('key', 'value_b');

		$this->assertEquals('value_a', $session1->get('key'));
		$this->assertEquals('value_b', $session2->get('key'));
	}

	/**
	 * Test commit returns true.
	 */
	public function test_commit(): void {

		$session = new Session_Cookie('test_commit');

		$session->set('key', 'value');

		// commit() calls setcookie which may fail in test env, but should not throw
		@$result = $session->commit();

		$this->assertTrue($result);
	}

	/**
	 * Test destroy returns result.
	 */
	public function test_destroy(): void {

		$session = new Session_Cookie('test_destroy');

		$session->set('key', 'value');

		// destroy() calls setcookie which may fail in test env
		@$result = $session->destroy();

		// destroy returns the result of Cookie::delete()
		$this->assertIsBool($result);
	}
}
