<?php
/**
 * Asset Helpers
 *
 * @package WP_Ultimo\Functions
 * @since   2.0.11
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Returns the URL for assets inside the assets folder.
 *
 * @since 2.0.0
 *
 * @param string $asset Asset file name with the extention.
 * @param string $assets_dir Assets sub-directory. Defaults to 'img'.
 * @param string $base_dir   Base dir. Defaults to 'assets'.
 * @return string
 */
function wu_get_asset($asset, $assets_dir = 'img', $base_dir = 'assets') {

	if ( ! defined('SCRIPT_DEBUG') || ! SCRIPT_DEBUG) {
		$asset = preg_replace('/(?<!\.min)(\.js|\.css)/', '.min$1', $asset);
	}

	return wu_url("$base_dir/$assets_dir/$asset");
}

/**
 * Checks if the current admin page belongs to WP Ultimo.
 *
 * Used to guard asset enqueues so that WP Ultimo scripts and styles are only
 * loaded on WP Ultimo admin pages, not on every page in the network admin.
 *
 * Detection relies on the hook suffix passed to `admin_enqueue_scripts`. All
 * WP Ultimo admin pages register with an ID prefixed by `wp-ultimo`, which
 * WordPress uses when generating the page hook (e.g., `toplevel_page_wp-ultimo`,
 * `wp-ultimo_page_wp-ultimo-settings`). The hook suffix always contains the
 * page slug, so checking for `wp-ultimo` is reliable.
 *
 * @since 2.4.2
 *
 * @param string $hook_suffix The hook suffix passed to `admin_enqueue_scripts`.
 * @return bool True if the current page is a WP Ultimo admin page.
 */
function wu_is_wu_page(string $hook_suffix = ''): bool {

	if ('' === $hook_suffix) {
		$screen      = get_current_screen();
		$hook_suffix = $screen ? (string) $screen->id : '';
	}

	return str_contains($hook_suffix, 'wp-ultimo');
}
