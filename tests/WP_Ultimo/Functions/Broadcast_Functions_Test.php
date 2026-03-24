<?php
/**
 * Tests for broadcast functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for broadcast functions.
 */
class Broadcast_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_broadcasts returns array.
	 */
	public function test_get_broadcasts_returns_array(): void {

		$result = wu_get_broadcasts();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_broadcasts with empty query.
	 */
	public function test_get_broadcasts_empty_query(): void {

		$result = wu_get_broadcasts([]);

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_broadcast returns false for nonexistent.
	 */
	public function test_get_broadcast_nonexistent(): void {

		$result = wu_get_broadcast(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_broadcast_by returns false for nonexistent.
	 */
	public function test_get_broadcast_by_nonexistent(): void {

		$result = wu_get_broadcast_by('id', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_create_broadcast creates a broadcast.
	 */
	public function test_create_broadcast(): void {

		$broadcast = wu_create_broadcast([
			'type'            => 'broadcast_notice',
			'notice_type'     => 'info',
			'title'           => 'Test Broadcast',
			'content'         => 'Test content',
			'status'          => 'publish',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($broadcast);
		$this->assertInstanceOf(\WP_Ultimo\Models\Broadcast::class, $broadcast);
	}

	/**
	 * Test wu_create_broadcast with email type.
	 */
	public function test_create_broadcast_email(): void {

		$broadcast = wu_create_broadcast([
			'type'            => 'broadcast_email',
			'title'           => 'Test Email Broadcast',
			'content'         => 'Email content',
			'status'          => 'publish',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($broadcast);
		$this->assertInstanceOf(\WP_Ultimo\Models\Broadcast::class, $broadcast);
	}

	/**
	 * Test wu_get_broadcasts with type filter.
	 */
	public function test_get_broadcasts_with_type_filter(): void {

		$result = wu_get_broadcasts([
			'type__in' => ['broadcast_notice'],
		]);

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_create_broadcast default values.
	 */
	public function test_create_broadcast_defaults(): void {

		$broadcast = wu_create_broadcast([
			'title'           => 'Default Test',
			'content'         => 'Default content',
			'status'          => 'publish',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($broadcast);
	}
}
