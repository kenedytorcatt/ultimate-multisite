<?php
/**
 * Tests for the Signup_Metrics class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

use WP_UnitTestCase;

/**
 * Tests for the Signup_Metrics class.
 *
 * @group signup-metrics
 */
class Signup_Metrics_Test extends WP_UnitTestCase {

	// ------------------------------------------------------------------
	// Singleton
	// ------------------------------------------------------------------

	/**
	 * Test that Signup_Metrics is a singleton.
	 */
	public function test_is_singleton(): void {

		$a = Signup_Metrics::get_instance();
		$b = Signup_Metrics::get_instance();

		$this->assertSame($a, $b);
	}

	// ------------------------------------------------------------------
	// register_event_types
	// ------------------------------------------------------------------

	/**
	 * Test that checkout_started event type is registered.
	 */
	public function test_checkout_started_event_type_registered(): void {

		// Trigger registration.
		do_action('wu_register_all_events');

		$event_types = wu_get_event_types();

		$this->assertArrayHasKey('checkout_started', $event_types);
	}

	/**
	 * Test that checkout_completed event type is registered.
	 */
	public function test_checkout_completed_event_type_registered(): void {

		do_action('wu_register_all_events');

		$event_types = wu_get_event_types();

		$this->assertArrayHasKey('checkout_completed', $event_types);
	}

	/**
	 * Test that checkout_step_completed event type is registered.
	 */
	public function test_checkout_step_completed_event_type_registered(): void {

		do_action('wu_register_all_events');

		$event_types = wu_get_event_types();

		$this->assertArrayHasKey('checkout_step_completed', $event_types);
	}

	/**
	 * Test that checkout_failed event type is registered.
	 */
	public function test_checkout_failed_event_type_registered(): void {

		do_action('wu_register_all_events');

		$event_types = wu_get_event_types();

		$this->assertArrayHasKey('checkout_failed', $event_types);
	}

	// ------------------------------------------------------------------
	// track_checkout_failed
	// ------------------------------------------------------------------

	/**
	 * Test that track_checkout_failed passes errors through unchanged.
	 */
	public function test_track_checkout_failed_passes_errors_through(): void {

		$metrics = Signup_Metrics::get_instance();

		$error = new \WP_Error('test_error', 'Test error message');

		$result = $metrics->track_checkout_failed($error, null);

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertTrue($result->has_errors());
		$this->assertEquals('test_error', $result->get_error_code());
	}

	/**
	 * Test that track_checkout_failed returns non-error input unchanged.
	 */
	public function test_track_checkout_failed_returns_non_error_unchanged(): void {

		$metrics = Signup_Metrics::get_instance();

		$non_error = new \WP_Error();

		$result = $metrics->track_checkout_failed($non_error, null);

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertFalse($result->has_errors());
	}

	// ------------------------------------------------------------------
	// Dashboard_Statistics integration
	// ------------------------------------------------------------------

	/**
	 * Test that get_data_signup_funnel returns expected keys.
	 */
	public function test_dashboard_statistics_signup_funnel_keys(): void {

		$stats = new Dashboard_Statistics(
			[
				'start_date' => '2025-01-01 00:00:00',
				'end_date'   => '2025-12-31 23:59:59',
				'types'      => [],
			]
		);

		$data = $stats->get_data_signup_funnel();

		$this->assertArrayHasKey('checkout_started', $data);
		$this->assertArrayHasKey('checkout_step_completed', $data);
		$this->assertArrayHasKey('checkout_completed', $data);
		$this->assertArrayHasKey('checkout_failed', $data);
		$this->assertArrayHasKey('conversion_rate', $data);
	}

	/**
	 * Test that conversion_rate is 0 when no events exist.
	 */
	public function test_dashboard_statistics_conversion_rate_zero_when_no_events(): void {

		$stats = new Dashboard_Statistics(
			[
				'start_date' => '2000-01-01 00:00:00',
				'end_date'   => '2000-01-02 00:00:00',
				'types'      => [],
			]
		);

		$data = $stats->get_data_signup_funnel();

		$this->assertEquals(0.0, $data['conversion_rate']);
	}

	/**
	 * Test that get_data_site_activity returns expected keys.
	 *
	 * Keys match the slugs produced by Post_Signup_Activity_Manager.
	 */
	public function test_dashboard_statistics_site_activity_keys(): void {

		$stats = new Dashboard_Statistics(
			[
				'start_date' => '2025-01-01 00:00:00',
				'end_date'   => '2025-12-31 23:59:59',
				'types'      => [],
			]
		);

		$data = $stats->get_data_site_activity();

		$this->assertArrayHasKey('subsite_post_created', $data);
		$this->assertArrayHasKey('subsite_cpt_created', $data);
		$this->assertArrayHasKey('subsite_user_registered', $data);
		$this->assertArrayHasKey('subsite_woocommerce_order', $data);
	}
}
