<?php
/**
 * Tests for event functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for event functions.
 */
class Event_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_event returns false for nonexistent.
	 */
	public function test_get_event_nonexistent(): void {

		$result = wu_get_event(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_event_by_slug returns false for nonexistent.
	 */
	public function test_get_event_by_slug_nonexistent(): void {

		$result = wu_get_event_by_slug('nonexistent_event_slug');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_events returns array.
	 */
	public function test_get_events_returns_array(): void {

		$result = wu_get_events();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_create_event creates an event.
	 */
	public function test_create_event(): void {

		$event = wu_create_event([
			'slug'            => 'test_event_' . wp_rand(),
			'severity'        => \WP_Ultimo\Models\Event::SEVERITY_NEUTRAL,
			'initiator'       => 'system',
			'object_type'     => 'network',
			'object_id'       => 0,
			'skip_validation' => true,
			'payload'         => [
				'key'       => 'test',
				'old_value' => 'old',
				'new_value' => 'new',
			],
		]);

		$this->assertNotWPError($event);
		$this->assertInstanceOf(\WP_Ultimo\Models\Event::class, $event);
	}

	/**
	 * Test wu_get_event retrieves created event.
	 */
	public function test_get_event_retrieves_created(): void {

		$event = wu_create_event([
			'slug'            => 'test_event_retrieve_' . wp_rand(),
			'severity'        => \WP_Ultimo\Models\Event::SEVERITY_NEUTRAL,
			'initiator'       => 'system',
			'object_type'     => 'network',
			'object_id'       => 0,
			'skip_validation' => true,
			'payload'         => [
				'key'       => 'test',
				'old_value' => 'old',
				'new_value' => 'new',
			],
		]);

		$this->assertNotWPError($event);

		$retrieved = wu_get_event($event->get_id());

		$this->assertNotFalse($retrieved);
		$this->assertEquals($event->get_id(), $retrieved->get_id());
	}

	/**
	 * Test wu_get_event_types returns array.
	 */
	public function test_get_event_types_returns_array(): void {

		$result = wu_get_event_types();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_event_types_as_options returns array.
	 */
	public function test_get_event_types_as_options_returns_array(): void {

		$result = wu_get_event_types_as_options();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_maybe_lazy_load_payload with array.
	 */
	public function test_maybe_lazy_load_payload_array(): void {

		$payload = wu_maybe_lazy_load_payload(['key' => 'value']);

		$this->assertIsArray($payload);
		$this->assertEquals('value', $payload['key']);
		$this->assertArrayHasKey('wu_version', $payload);
	}

	/**
	 * Test wu_maybe_lazy_load_payload with callable.
	 */
	public function test_maybe_lazy_load_payload_callable(): void {

		$payload = wu_maybe_lazy_load_payload(function () {
			return ['key' => 'lazy_value'];
		});

		$this->assertIsArray($payload);
		$this->assertEquals('lazy_value', $payload['key']);
		$this->assertArrayHasKey('wu_version', $payload);
	}
}
