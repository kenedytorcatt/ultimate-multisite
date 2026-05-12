<?php
/**
 * Plugin Name: KP Fix UM Blogname
 * Description: Forces correct blogname on subsites duplicated from Ultimate Multisite templates. Fixes David's bug in class-site-duplicator.php:326-327 where $args->title arrives empty via WooCommerce checkout flow, leaving the template's blogname (e.g. "Plantilla Belleza") instead of the customer's site name.
 * Version: 1.0.0
 * Author: KursoPro
 * Network: true
 *
 * BUG UPSTREAM:
 *   - Plugin: ultimate-multisite 2.6.2 (and all prior versions)
 *   - File:   inc/helpers/class-site-duplicator.php:326-327
 *   - Issue:  if ($args->title is empty) the blogname is not overwritten,
 *             so the clone keeps the template's "Plantilla X" name
 *
 * REAL IMPACT:
 *   - Blog 299 ikenabazan.kursopro.com — created 2026-04-14 with "Plantilla Belleza"
 *   - Blog 307 fomenthy.kursopro.com    — created 2026-04-16 with "Plantilla Creatividad"
 *   - Customer-facing WP emails went out with template name as subject prefix
 *   - Admin bar and tab title showed template name
 *
 * HOW THIS PATCH WORKS:
 *   - Hooks into `wu_duplicate_site` (fired after David's duplication completes)
 *   - If blogname starts with "Plantilla " → replaces with clean subdomain
 *   - Clean subdomain = first label of domain, capitalized
 *     (e.g. "ikenabazan.kursopro.com" → "Ikenabazan")
 *
 * REMOVAL CRITERIA:
 *   - Remove only when Ultimate Multisite ships a version whose changelog
 *     confirms a fallback in class-site-duplicator.php for empty $args->title
 *
 * @since 2026-04-17
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wu_duplicate_site',
	function ( $args ) {
		if ( empty( $args['site_id'] ) ) {
			return;
		}

		$site_id = (int) $args['site_id'];
		$current = get_blog_option( $site_id, 'blogname', '' );

		// Only act if blogname starts with "Plantilla " (the template leak signature).
		if ( stripos( $current, 'Plantilla ' ) !== 0 ) {
			return;
		}

		$details = get_blog_details( $site_id );
		if ( ! $details || empty( $details->domain ) ) {
			return;
		}

		// Extract first subdomain label → capitalize.
		$first_label = strtok( $details->domain, '.' );
		if ( empty( $first_label ) ) {
			return;
		}

		$new_name = ucfirst( $first_label );

		update_blog_option( $site_id, 'blogname', $new_name );

		if ( function_exists( 'error_log' ) ) {
			error_log(
				sprintf(
					'[KP Fix UM Blogname] Site %d renamed from %s to %s (domain: %s)',
					$site_id,
					$current,
					$new_name,
					$details->domain
				)
			);
		}
	},
	20
);
