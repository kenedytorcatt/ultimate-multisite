<?php
/**
 * Plugin Name: KP Fix UM Domain Mapping
 * Description: Fixes missing option_home filter in Ultimate Multisite domain mapping,
 *              flushes rewrite rules on new subsite creation, and enforces HTTPS scheme.
 * Version:     1.0.0
 * Author:      KursoPro
 * Network:     true
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_multisite() ) {
    return;
}

/**
 * Fix 1: Add the missing option_home filter to UM Domain_Mapping.
 *
 * Ultimate Multisite registers option_siteurl but NOT option_home,
 * causing get_permalink() to return the original subdomain URL
 * instead of the mapped domain.
 *
 * Hooks at plugins_loaded:99 so UM has already registered its filters.
 */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WP_Ultimo\\Domain_Mapping' ) ) {
        return;
    }

    $dm = \WP_Ultimo\Domain_Mapping::get_instance();

    if ( ! is_object( $dm ) || ! method_exists( $dm, 'mangle_url' ) ) {
        return;
    }

    // Mirror the existing option_siteurl filter for option_home.
    add_filter( 'option_home', [ $dm, 'mangle_url' ], 20 );
}, 99 );

/**
 * Fix 2: Flush rewrite rules after WP Ultimo duplicates a site.
 *
 * Without this, the new subsite keeps stale rewrite rules from the
 * template site, which can produce 404s on custom post types.
 */
add_action( 'wu_duplicate_site', function ( $new_site_id ) {
    if ( ! is_numeric( $new_site_id ) || (int) $new_site_id < 2 ) {
        return;
    }

    switch_to_blog( (int) $new_site_id );
    flush_rewrite_rules( true );
    restore_current_blog();
}, 20 );

/**
 * Fix 3: Enforce HTTPS on home/siteurl for newly initialised sites.
 *
 * If the main site runs on HTTPS the new subsite should too.
 * Runs at wp_initialize_site:999 (after UM's own handler at 10).
 */
add_action( 'wp_initialize_site', function ( $new_site ) {
    if ( ! is_object( $new_site ) || ! method_exists( $new_site, 'get' ) ) {
        // WP < 5.1 compat: $new_site may be a WP_Site object.
        if ( ! isset( $new_site->blog_id ) ) {
            return;
        }
        $blog_id = (int) $new_site->blog_id;
    } else {
        $blog_id = (int) $new_site->get( 'blog_id' );
    }

    if ( $blog_id < 2 ) {
        return;
    }

    // Only enforce if the main site uses HTTPS.
    $main_scheme = wp_parse_url( get_site_url( get_main_site_id() ), PHP_URL_SCHEME );
    if ( 'https' !== $main_scheme ) {
        return;
    }

    switch_to_blog( $blog_id );

    foreach ( [ 'home', 'siteurl' ] as $option ) {
        $value = get_option( $option );
        if ( is_string( $value ) && 0 === strpos( $value, 'http://' ) ) {
            update_option( $option, str_replace( 'http://', 'https://', $value ) );
        }
    }

    restore_current_blog();
}, 999 );
