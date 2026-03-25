<?php
/**
 * Signup Flow Metrics
 *
 * Tracks the checkout/registration funnel from page view through to
 * successful membership creation. Events are stored in the wu_events
 * table and are queryable via the existing events infrastructure.
 *
 * @package WP_Ultimo
 * @subpackage Metrics
 * @since 2.5.0
 */

namespace WP_Ultimo;

use WP_Ultimo\Models\Event;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Tracks signup funnel events.
 *
 * Hooks into the checkout lifecycle to record:
 *  - checkout_started      : user lands on a checkout page
 *  - checkout_step_completed: user advances past a step
 *  - checkout_completed    : payment/order processed successfully
 *  - checkout_failed       : checkout processing returned an error
 *
 * @since 2.5.0
 */
class Signup_Metrics {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Option key used to store the daily signup-started counter.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const OPTION_DAILY_STARTS = 'wu_signup_daily_starts';

	/**
	 * Registers all hooks.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function init(): void {

		// Track when a user lands on a checkout page.
		add_action('wu_checkout_element_render', [$this, 'track_checkout_started'], 10, 1);

		// Track when a checkout step is submitted successfully.
		add_action('wu_checkout_order_created', [$this, 'track_checkout_step_completed'], 10, 4);

		// Track successful checkout completion.
		add_action('wu_checkout_done', [$this, 'track_checkout_completed'], 10, 6);

		// Track checkout failures.
		add_filter('wu_checkout_errors', [$this, 'track_checkout_failed'], 10, 2);

		// Register event types for webhooks/emails.
		add_action('wu_register_all_events', [$this, 'register_event_types']);
	}

	/**
	 * Fires when a checkout element is rendered (user views the checkout page).
	 *
	 * @since 2.5.0
	 *
	 * @param \WP_Ultimo\UI\Checkout_Element $element The checkout element being rendered.
	 * @return void
	 */
	public function track_checkout_started($element): void {

		// Only track once per page load (avoid double-counting on AJAX refreshes).
		if (did_action('wu_checkout_element_render') > 1) {
			return;
		}

		$form_slug = method_exists($element, 'get_pre_loaded_attribute')
			? $element->get_pre_loaded_attribute('slug', 'unknown')
			: 'unknown';

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_INFO,
				'slug'        => 'checkout_started',
				'object_type' => 'network',
				'object_id'   => 0,
				'initiator'   => 'system',
				'payload'     => [
					'form_slug'  => sanitize_key((string) $form_slug),
					'user_id'    => get_current_user_id(),
					'ip_address' => $this->get_client_ip(),
					'referrer'   => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
				],
			]
		);
	}

	/**
	 * Fires when a checkout order is fully assembled (step completed).
	 *
	 * @since 2.5.0
	 *
	 * @param \WP_Ultimo\Checkout\Cart    $order      The cart/order object.
	 * @param \WP_Ultimo\Models\Customer  $customer   The customer.
	 * @param \WP_Ultimo\Models\Membership $membership The primary membership.
	 * @param \WP_Ultimo\Models\Payment   $payment    The payment.
	 * @return void
	 */
	public function track_checkout_step_completed($order, $customer, $membership, $payment): void {

		$plan = $order->get_plan();

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_INFO,
				'slug'        => 'checkout_step_completed',
				'object_type' => 'membership',
				'object_id'   => $membership ? $membership->get_id() : 0,
				'initiator'   => 'system',
				'payload'     => [
					'customer_id'   => $customer ? $customer->get_id() : 0,
					'membership_id' => $membership ? $membership->get_id() : 0,
					'plan_id'       => $plan ? $plan->get_id() : 0,
					'plan_slug'     => $plan ? $plan->get_slug() : '',
					'cart_type'     => $order->get_cart_type(),
					'is_free'       => $order->is_free(),
				],
			]
		);
	}

	/**
	 * Fires after a checkout is fully processed (payment gateway returned success).
	 *
	 * @since 2.5.0
	 *
	 * @param \WP_Ultimo\Models\Payment    $payment    The payment.
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer   The customer.
	 * @param \WP_Ultimo\Checkout\Cart     $order      The cart.
	 * @param string                       $type       Cart type.
	 * @param \WP_Ultimo\Checkout\Checkout $checkout   The checkout instance.
	 * @return void
	 */
	public function track_checkout_completed($payment, $membership, $customer, $order, $type, $checkout): void {

		$plan = $order ? $order->get_plan() : null;

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_SUCCESS,
				'slug'        => 'checkout_completed',
				'object_type' => 'membership',
				'object_id'   => $membership ? $membership->get_id() : 0,
				'initiator'   => 'system',
				'payload'     => [
					'customer_id'    => $customer ? $customer->get_id() : 0,
					'membership_id'  => $membership ? $membership->get_id() : 0,
					'payment_id'     => $payment ? $payment->get_id() : 0,
					'payment_total'  => $payment ? $payment->get_total() : 0,
					'payment_status' => $payment ? $payment->get_status() : '',
					'plan_id'        => $plan ? $plan->get_id() : 0,
					'plan_slug'      => $plan ? $plan->get_slug() : '',
					'cart_type'      => $order ? $order->get_cart_type() : $type,
					'gateway'        => $payment ? $payment->get_gateway() : '',
					'is_free'        => $order ? $order->is_free() : false,
				],
			]
		);
	}

	/**
	 * Fires when the checkout returns errors (checkout failed).
	 *
	 * Passes errors through unchanged — this is a filter so we can observe
	 * the error without blocking the normal error-handling flow.
	 *
	 * @since 2.5.0
	 *
	 * @param \WP_Error                    $errors   The checkout errors.
	 * @param \WP_Ultimo\Checkout\Checkout $checkout The checkout instance.
	 * @return \WP_Error
	 */
	public function track_checkout_failed($errors, $checkout): \WP_Error {

		if ( ! is_wp_error($errors) || ! $errors->has_errors()) {
			return $errors;
		}

		$error_codes    = $errors->get_error_codes();
		$error_messages = [];

		foreach ($error_codes as $code) {
			$error_messages[ $code ] = $errors->get_error_message($code);
		}

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_WARNING,
				'slug'        => 'checkout_failed',
				'object_type' => 'network',
				'object_id'   => 0,
				'initiator'   => 'system',
				'payload'     => [
					'error_codes'    => $error_codes,
					'error_messages' => $error_messages,
					'user_id'        => get_current_user_id(),
				],
			]
		);

		return $errors;
	}

	/**
	 * Registers signup funnel event types for webhooks and emails.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function register_event_types(): void {

		wu_register_event_type(
			'checkout_started',
			[
				'name'            => __('Checkout Started', 'ultimate-multisite'),
				'desc'            => __('Fired when a visitor lands on a checkout page.', 'ultimate-multisite'),
				'payload'         => [
					'form_slug'  => 'default-checkout',
					'user_id'    => 0,
					'ip_address' => '127.0.0.1',
					'referrer'   => '',
				],
				'deprecated_args' => [],
			]
		);

		wu_register_event_type(
			'checkout_step_completed',
			[
				'name'            => __('Checkout Step Completed', 'ultimate-multisite'),
				'desc'            => __('Fired when a checkout step is submitted and the order is assembled.', 'ultimate-multisite'),
				'payload'         => fn() => array_merge(
					wu_generate_event_payload('membership'),
					wu_generate_event_payload('customer'),
					[
						'cart_type' => 'new',
						'is_free'   => false,
					]
				),
				'deprecated_args' => [],
			]
		);

		wu_register_event_type(
			'checkout_completed',
			[
				'name'            => __('Checkout Completed', 'ultimate-multisite'),
				'desc'            => __('Fired when a checkout is fully processed and the payment gateway returns success.', 'ultimate-multisite'),
				'payload'         => fn() => array_merge(
					wu_generate_event_payload('payment'),
					wu_generate_event_payload('membership'),
					wu_generate_event_payload('customer'),
					[
						'cart_type' => 'new',
						'gateway'   => 'free',
						'is_free'   => true,
					]
				),
				'deprecated_args' => [],
			]
		);

		wu_register_event_type(
			'checkout_failed',
			[
				'name'            => __('Checkout Failed', 'ultimate-multisite'),
				'desc'            => __('Fired when a checkout attempt returns validation or gateway errors.', 'ultimate-multisite'),
				'payload'         => [
					'error_codes'    => ['example_error'],
					'error_messages' => ['example_error' => 'Example error message'],
					'user_id'        => 0,
				],
				'deprecated_args' => [],
			]
		);
	}

	/**
	 * Returns the client IP address, respecting common proxy headers.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	protected function get_client_ip(): string {

		$headers = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ($headers as $header) {
			if ( ! empty($_SERVER[ $header ])) {
				$ip = sanitize_text_field(wp_unslash($_SERVER[ $header ]));

				// X-Forwarded-For can be a comma-separated list; take the first.
				$ip = explode(',', $ip)[0];
				$ip = trim($ip);

				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}

		return '';
	}
}
