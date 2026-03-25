<?php
/**
 * Post-Signup Activity Tracker
 *
 * Tracks actions that happen inside customer sub-sites after signup:
 * - Post / custom post type creation
 * - New user registrations on sub-sites
 * - WooCommerce order placement (when WooCommerce is active)
 *
 * Events are stored in the network-level wu_events table so the network
 * admin can see activity across all sub-sites in one place.
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
 * Tracks post-signup activity across customer sub-sites.
 *
 * @since 2.5.0
 */
class Activity_Tracker {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Post types that are excluded from tracking to reduce noise.
	 * Transient/revision/auto-draft types are never interesting.
	 *
	 * @since 2.5.0
	 * @var array
	 */
	const EXCLUDED_POST_TYPES = [
		'revision',
		'auto-draft',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
	];

	/**
	 * Registers all hooks.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function init(): void {

		// Track post/CPT creation on any sub-site.
		add_action('transition_post_status', [$this, 'track_post_published'], 10, 3);

		// Track new user registrations on sub-sites.
		add_action('user_register', [$this, 'track_user_registered'], 10, 1);

		// Track WooCommerce orders (fires on any sub-site that has WooCommerce).
		add_action('woocommerce_new_order', [$this, 'track_woocommerce_order'], 10, 2);

		// Register event types for webhooks/emails.
		add_action('wu_register_all_events', [$this, 'register_event_types']);
	}

	/**
	 * Fires when a post transitions to 'publish' status.
	 *
	 * @since 2.5.0
	 *
	 * @param string   $new_status The new post status.
	 * @param string   $old_status The old post status.
	 * @param \WP_Post $post       The post object.
	 * @return void
	 */
	public function track_post_published(string $new_status, string $old_status, \WP_Post $post): void {

		// Only track the transition TO published.
		if ('publish' !== $new_status || 'publish' === $old_status) {
			return;
		}

		// Skip excluded post types.
		if (in_array($post->post_type, self::EXCLUDED_POST_TYPES, true)) {
			return;
		}

		// Only track on customer-owned sub-sites.
		$blog_id = get_current_blog_id();

		if ( ! $this->is_customer_site($blog_id)) {
			return;
		}

		$site = wu_get_site_by_blog_id($blog_id);

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_INFO,
				'slug'        => 'site_post_published',
				'object_type' => 'site',
				'object_id'   => $site ? $site->get_id() : 0,
				'initiator'   => 'system',
				'payload'     => [
					'blog_id'       => $blog_id,
					'site_id'       => $site ? $site->get_id() : 0,
					'post_id'       => $post->ID,
					'post_type'     => $post->post_type,
					'post_title'    => $post->post_title,
					'post_author'   => (int) $post->post_author,
					'membership_id' => $site ? $site->get_membership_id() : 0,
				],
			]
		);
	}

	/**
	 * Fires when a new user is registered on any sub-site.
	 *
	 * @since 2.5.0
	 *
	 * @param int $user_id The newly registered user ID.
	 * @return void
	 */
	public function track_user_registered(int $user_id): void {

		$blog_id = get_current_blog_id();

		// Skip the main network site — we only care about sub-site registrations.
		if (is_main_site($blog_id)) {
			return;
		}

		if ( ! $this->is_customer_site($blog_id)) {
			return;
		}

		$site = wu_get_site_by_blog_id($blog_id);

		$user = get_userdata($user_id);

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_INFO,
				'slug'        => 'site_user_registered',
				'object_type' => 'site',
				'object_id'   => $site ? $site->get_id() : 0,
				'initiator'   => 'system',
				'payload'     => [
					'blog_id'       => $blog_id,
					'site_id'       => $site ? $site->get_id() : 0,
					'user_id'       => $user_id,
					'user_login'    => $user ? $user->user_login : '',
					'user_email'    => $user ? $user->user_email : '',
					'membership_id' => $site ? $site->get_membership_id() : 0,
				],
			]
		);
	}

	/**
	 * Fires when a WooCommerce order is created on a sub-site.
	 *
	 * @since 2.5.0
	 *
	 * @param int       $order_id The WooCommerce order ID.
	 * @param \WC_Order $order    The WooCommerce order object.
	 * @return void
	 */
	public function track_woocommerce_order(int $order_id, $order): void {

		$blog_id = get_current_blog_id();

		if ( ! $this->is_customer_site($blog_id)) {
			return;
		}

		$site = wu_get_site_by_blog_id($blog_id);

		$total    = $order && method_exists($order, 'get_total') ? (float) $order->get_total() : 0.0;
		$currency = $order && method_exists($order, 'get_currency') ? $order->get_currency() : '';
		$status   = $order && method_exists($order, 'get_status') ? $order->get_status() : '';

		wu_create_event(
			[
				'severity'    => Event::SEVERITY_INFO,
				'slug'        => 'site_woocommerce_order',
				'object_type' => 'site',
				'object_id'   => $site ? $site->get_id() : 0,
				'initiator'   => 'system',
				'payload'     => [
					'blog_id'       => $blog_id,
					'site_id'       => $site ? $site->get_id() : 0,
					'order_id'      => $order_id,
					'order_total'   => $total,
					'order_currency'=> $currency,
					'order_status'  => $status,
					'membership_id' => $site ? $site->get_membership_id() : 0,
				],
			]
		);
	}

	/**
	 * Registers post-signup activity event types for webhooks/emails.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function register_event_types(): void {

		wu_register_event_type(
			'site_post_published',
			[
				'name'            => __('Sub-site Post Published', 'ultimate-multisite'),
				'desc'            => __('Fired when a post or custom post type is published on a customer sub-site.', 'ultimate-multisite'),
				'payload'         => fn() => array_merge(
					wu_generate_event_payload('site'),
					[
						'post_id'    => 1,
						'post_type'  => 'post',
						'post_title' => 'Example Post',
						'post_author'=> 1,
					]
				),
				'deprecated_args' => [],
			]
		);

		wu_register_event_type(
			'site_user_registered',
			[
				'name'            => __('Sub-site User Registered', 'ultimate-multisite'),
				'desc'            => __('Fired when a new user registers on a customer sub-site.', 'ultimate-multisite'),
				'payload'         => fn() => array_merge(
					wu_generate_event_payload('site'),
					[
						'user_id'    => 1,
						'user_login' => 'example_user',
						'user_email' => 'user@example.com',
					]
				),
				'deprecated_args' => [],
			]
		);

		wu_register_event_type(
			'site_woocommerce_order',
			[
				'name'            => __('Sub-site WooCommerce Order', 'ultimate-multisite'),
				'desc'            => __('Fired when a WooCommerce order is placed on a customer sub-site.', 'ultimate-multisite'),
				'payload'         => fn() => array_merge(
					wu_generate_event_payload('site'),
					[
						'order_id'       => 1,
						'order_total'    => 49.99,
						'order_currency' => 'USD',
						'order_status'   => 'pending',
					]
				),
				'deprecated_args' => [],
			]
		);
	}

	/**
	 * Checks whether a given blog ID belongs to a WP Ultimo customer-owned site.
	 *
	 * @since 2.5.0
	 *
	 * @param int $blog_id The blog ID to check.
	 * @return bool
	 */
	protected function is_customer_site(int $blog_id): bool {

		$site = wu_get_site_by_blog_id($blog_id);

		if ( ! $site) {
			return false;
		}

		return \WP_Ultimo\Database\Sites\Site_Type::CUSTOMER_OWNED === $site->get_type();
	}
}
