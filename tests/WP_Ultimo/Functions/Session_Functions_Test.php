<?php
/**
 * Tests for session functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for session functions.
 */
class Session_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_session returns a Session_Cookie instance.
	 */
	public function test_wu_get_session_returns_session_cookie(): void {

		$session = wu_get_session('test_session');

		$this->assertInstanceOf(\WP_Ultimo\Session_Cookie::class, $session);
	}

	/**
	 * Test wu_get_session returns same instance for same key.
	 */
	public function test_wu_get_session_returns_same_instance(): void {

		$session1 = wu_get_session('same_key');
		$session2 = wu_get_session('same_key');

		$this->assertSame($session1, $session2);
	}

	/**
	 * Test wu_get_session returns different instances for different keys.
	 */
	public function test_wu_get_session_different_keys(): void {

		$session1 = wu_get_session('key_a');
		$session2 = wu_get_session('key_b');

		$this->assertNotSame($session1, $session2);
	}
}
