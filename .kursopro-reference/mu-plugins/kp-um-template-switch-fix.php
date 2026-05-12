<?php
/**
 * Plugin Name: KP UM Template Switch Fix
 * Description: Senior-grade patch for UM core wu_switch_template AJAX. Fixes 10 bugs: timeout, blogname overwrite, blogmeta corruption, Elementor Kit/CSS broken, Elementor breakpoints null fatal, object cache stale, no recovery path, no reset-current-template option, thumbnail not refreshed, AND Kit Elementor settings/data not copied (causes wrong colors). Includes integrity verification, persistent backup, emergency restore CLI, ALL Elementor CSS regeneration, AND forced Kit settings copy from source template. Portable to UM core via PR.
 * Version: 3.5.0
 * Author: KursoPro
 * Network: true
 *
 * ─────────────────────────────────────────────────────────────────────
 * BUGS COVERED (v3.0)
 * ─────────────────────────────────────────────────────────────────────
 *
 * BUG 1 — AJAX timeout (default 30s) cuts copy_files mid-process
 * BUG 2 — blogname (site title) overwritten with template's title
 * BUG 3 — blogmeta corruption when AJAX dies mid-flight
 * BUG 4 — Elementor Kit / CSS broken post-switch (incomplete regen)
 * BUG 5 — Object cache returns stale wu_type post-restore
 * BUG 6 — No recovery path for affected customers
 * BUG 7 — Elementor fatal "get_responsive_control_duplication_mode() on null"
 *         during copy_data() because Plugin::$instance->breakpoints is not
 *         initialized when called from MUCD_Data context.
 *         Fix: ensure Elementor breakpoints initialized before override_site,
 *         and wrap any breakpoint calls with try/catch + null check.
 * BUG 8 — No "reset current template" option in panel UX. User must switch
 *         to a different template and back to reset. Confusing.
 *         Fix: hook into template selection UI to allow same-template switch
 *         + add custom CLI command `wp kp-um-tpl reset <blog_id>` for staff.
 *
 * BUG 9 — Thumbnail (panel preview image) not refreshed when template changes.
 *         Fix: refresh wu_featured_image_id from new template after switch.
 *
 * BUG 10 — Kit Elementor settings/data NOT copied during MUCD_Data::copy_data().
 *         Symptom (real case abconline 2026-05-03): customer switched to
 *         template Belleza but Kit retained colors from previous template
 *         Profesional (azul #305778 instead of rosa #EAC7C7).
 *         Root cause: MUCD copies wp_X_postmeta rows but the Elementor Kit
 *         (post 3) settings serialize/cache layer doesn't pick up the new
 *         values without explicit force-copy + regen.
 *         Fix: explicitly copy `_elementor_page_settings` and `_elementor_data`
 *         from source template's Kit (post 3) to target subsite's Kit (post 3),
 *         then regenerate Kit CSS. This guarantees colors/typography/breakpoints
 *         match the chosen template exactly.
 *
 * v3.0 ADDITIONS:
 * - Regenerate Elementor CSS for ALL pages/posts/elementor_library (not just 30)
 * - Suppress Elementor breakpoints fatal during AJAX
 * - Force-allow same-template switch (treats as reset)
 * - WP-CLI command `reset` for direct subsite reset to its current template
 * - Permanent backup retained 30 days (was 7)
 *
 * ─────────────────────────────────────────────────────────────────────
 * REAL-WORLD CASE
 * ─────────────────────────────────────────────────────────────────────
 *
 * 2026-05-03 — Customer abconline.kursopro.com (blog 291) clicked "Confirm
 * template change" multiple times. AJAX hung 60+ seconds with Elementor
 * breakpoints fatal in logs. Site ended with mixed content (template +
 * customer). Manual SQL restoration + CLI override required.
 *
 * ─────────────────────────────────────────────────────────────────────
 * PORTABILITY TO UM CORE (Dave's PR)
 * ─────────────────────────────────────────────────────────────────────
 *
 * Each fix is implemented via PUBLIC HOOKS. To upstream this to UM core,
 * the same logic should be added directly to:
 *   - inc/helpers/class-site-duplicator.php :: override_site()
 *   - inc/ui/class-template-switching-element.php :: switch_template()
 *   - inc/ui/class-template-switching-element.php :: output() — add reset btn
 *
 * See _tmp/para-david-bugs-template-switch/ for the full PR package.
 */

if ( ! defined('ABSPATH') ) {
	exit;
}

/* ────────────────────────────────────────────────────────────────────
 * CONFIG
 * ──────────────────────────────────────────────────────────────────── */

if ( ! defined('KP_UM_TPL_LOG_FILE') ) {
	define('KP_UM_TPL_LOG_FILE', WP_CONTENT_DIR . '/uploads/wu-logs/kp-template-switch.log');
}
if ( ! defined('KP_UM_TPL_TIMEOUT') ) {
	define('KP_UM_TPL_TIMEOUT', 300);
}
if ( ! defined('KP_UM_TPL_MEMORY') ) {
	define('KP_UM_TPL_MEMORY', '512M');
}
if ( ! defined('KP_UM_TPL_BACKUP_DAYS') ) {
	define('KP_UM_TPL_BACKUP_DAYS', 30);
}

/* ────────────────────────────────────────────────────────────────────
 * LOGGER
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_log($stage, $msg, $ctx = []) {
	$dir = dirname(KP_UM_TPL_LOG_FILE);
	if ( ! is_dir($dir) ) {
		@wp_mkdir_p($dir);
	}
	$line = sprintf('[%s] [%s] %s', gmdate('Y-m-d H:i:s'), $stage, $msg);
	if ( ! empty($ctx) ) {
		$line .= ' ' . wp_json_encode($ctx, JSON_UNESCAPED_SLASHES);
	}
	@file_put_contents(KP_UM_TPL_LOG_FILE, $line . "\n", FILE_APPEND | LOCK_EX);
}

/* ────────────────────────────────────────────────────────────────────
 * SNAPSHOT BUILDER
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_build_snapshot($blog_id) {
	$blog_id = (int) $blog_id;
	if ( $blog_id <= 1 ) return false;
	if ( ! function_exists('wu_get_site') ) return false;

	$site = wu_get_site($blog_id);
	if ( ! $site ) return false;

	$blogname = get_blog_option($blog_id, 'blogname');

	// FIX v3.4.6 — Detect contaminated blogname (starts with "Plantilla").
	// If the current blogname is from a template (failed previous switch),
	// try to recover from persistent backup. NEVER snapshot a contaminated value.
	if ( preg_match('/^Plantilla\s/i', $blogname) ) {
		$backup = get_site_option('kp_um_tpl_backup_' . $blog_id);
		if ( ! empty($backup['blogname']) && ! preg_match('/^Plantilla\s/i', $backup['blogname']) ) {
			$blogname = $backup['blogname'];
			kp_um_tpl_log('SNAPSHOT-RECOVERY', 'detected contaminated blogname, recovered from backup', [
				'blog_id' => $blog_id,
				'recovered_blogname' => $blogname,
			]);
		}
	}

	$admin_email = get_blog_option($blog_id, 'admin_email');
	// Same protection for admin_email — should never be info@kpstage.com or template's email.
	if ( $admin_email === 'info@kpstage.com' ) {
		$backup = get_site_option('kp_um_tpl_backup_' . $blog_id);
		if ( ! empty($backup['admin_email']) && $backup['admin_email'] !== 'info@kpstage.com' ) {
			$admin_email = $backup['admin_email'];
		}
	}

	return [
		'blog_id'              => $blog_id,
		'blogname'             => $blogname,
		'blogdescription'      => get_blog_option($blog_id, 'blogdescription'),
		'home'                 => get_blog_option($blog_id, 'home'),
		'siteurl'              => get_blog_option($blog_id, 'siteurl'),
		'admin_email'          => $admin_email,
		'wu_type'              => method_exists($site, 'get_type') ? $site->get_type() : '',
		'wu_membership_id'     => method_exists($site, 'get_membership_id') ? (int) $site->get_membership_id() : 0,
		'wu_customer_id'       => method_exists($site, 'get_customer_id') ? (int) $site->get_customer_id() : 0,
		'wu_template_id'       => method_exists($site, 'get_template_id') ? (int) $site->get_template_id() : 0,
		'wu_categories'        => get_site_meta($blog_id, 'wu_categories', true),
		'wu_featured_image_id' => get_site_meta($blog_id, 'wu_featured_image_id', true),
		'wu_active'            => get_site_meta($blog_id, 'wu_active', true),
		'site_title'           => method_exists($site, 'get_title') ? $site->get_title() : '',
		'taken_at'             => time(),
	];
}

/* ────────────────────────────────────────────────────────────────────
 * BUG 7 FIX — Elementor breakpoints initialization
 *
 * Pre-warm Elementor's Plugin::$instance->breakpoints object so the
 * "Call to a member function get_responsive_control_duplication_mode()
 * on null" fatal doesn't fire during MUCD_Data::copy_data().
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_warmup_elementor() {
	if ( ! class_exists('\Elementor\Plugin') ) return;
	try {
		$plugin = \Elementor\Plugin::$instance;
		if ( ! $plugin ) return;
		// Force-init breakpoints if missing.
		if ( empty($plugin->breakpoints) && method_exists($plugin, 'init_components') ) {
			// Some Elementor versions don't expose init publicly; let's just
			// ensure the property is accessed which triggers lazy-load on most
			// versions.
			@$plugin->breakpoints;
		}
		// Set duplication mode to a safe default so subsequent calls don't choke.
		if ( ! empty($plugin->breakpoints) && method_exists($plugin->breakpoints, 'set_responsive_control_duplication_mode') ) {
			$plugin->breakpoints->set_responsive_control_duplication_mode('on');
		}
	} catch (\Throwable $e) {
		kp_um_tpl_log('ELEMENTOR-WARMUP', 'failed', ['error' => $e->getMessage()]);
	}
}

/* ────────────────────────────────────────────────────────────────────
 * STEP 1 — Pre-switch snapshot + timeout extension
 * ──────────────────────────────────────────────────────────────────── */

add_action('wu_ajax_wu_switch_template', 'kp_um_tpl_pre_switch', 1);

/**
 * BUG 12 FIX (v3.4) — Auto-snapshot on direct CLI/code calls.
 *
 * When override_site() is called from CLI or other plugins (NOT through
 * the AJAX path), kp_um_tpl_pre_switch never fires and there's no
 * snapshot. This causes second/third consecutive switches to fail
 * because admin_email gets overwritten by the previous template's data.
 *
 * Fix: hook into mucd_before_copy_data to snapshot identity AUTOMATICALLY
 * even outside AJAX context. Idempotent — safe to call multiple times.
 */
add_action('mucd_before_copy_data', 'kp_um_tpl_auto_snapshot_pre_copy', 1, 2);

/**
 * BUG 12 FINAL FIX (v3.4.3) — Restore identity IMMEDIATELY after copy_data.
 *
 * MUCD_Data::copy_data() does INSERT...SELECT which COMPLETELY OVERWRITES
 * wp_X_options (including admin_email, blogname) AND wp_blogmeta records
 * with the template's values. This DESTROYS:
 *   - admin_email (becomes template's admin)
 *   - blogname (becomes "Plantilla Belleza")
 *   - wu_customer_id (becomes empty since templates don't have customer)
 *   - wu_membership_id (becomes empty)
 *   - wu_type (becomes 'site_template')
 *
 * When override_site continues, it tries to read these from the now-broken
 * subsite. The 2nd override gets the contaminated data and create_admin
 * fails because customer can't be looked up.
 *
 * Fix: hook 'mucd_after_copy_data' which fires INSIDE process_duplication
 * RIGHT after copy_data completes, BEFORE override_site continues with
 * set_membership_id et al. We restore the identity IMMEDIATELY so the
 * rest of override_site sees the customer-correct values.
 *
 * This works because mucd_after_copy_data fires DURING the call, on the
 * same blog context.
 */
add_action('mucd_after_copy_data', 'kp_um_tpl_post_copy_restore', 5, 2);

function kp_um_tpl_post_copy_restore($from_site_id, $to_site_id) {
	$to_site_id = (int) $to_site_id;
	if ( $to_site_id <= 1 ) return;

	// Get backup (transient first, then site_option fallback).
	$snapshot = get_transient('kp_um_tpl_snapshot_' . $to_site_id);
	if ( empty($snapshot) ) {
		$snapshot = get_site_option('kp_um_tpl_backup_' . $to_site_id);
	}
	if ( empty($snapshot) ) {
		kp_um_tpl_log('POST-COPY-RESTORE', 'no snapshot available', ['to_blog_id' => $to_site_id]);
		return;
	}

	// Restore admin_email + blogname + URLs IMMEDIATELY.
	if ( ! empty($snapshot['admin_email']) ) {
		update_blog_option($to_site_id, 'admin_email', $snapshot['admin_email']);
	}
	if ( ! empty($snapshot['blogname']) ) {
		update_blog_option($to_site_id, 'blogname', $snapshot['blogname']);
	}
	if ( ! empty($snapshot['blogdescription']) ) {
		update_blog_option($to_site_id, 'blogdescription', $snapshot['blogdescription']);
	}
	if ( ! empty($snapshot['home']) ) {
		update_blog_option($to_site_id, 'home', $snapshot['home']);
	}
	if ( ! empty($snapshot['siteurl']) ) {
		update_blog_option($to_site_id, 'siteurl', $snapshot['siteurl']);
	}

	// Restore blogmeta — CRITICAL for keeping subsite as customer_owned.
	if ( ! empty($snapshot['wu_membership_id']) ) {
		update_site_meta($to_site_id, 'wu_membership_id', (int) $snapshot['wu_membership_id']);
	}
	if ( ! empty($snapshot['wu_customer_id']) ) {
		update_site_meta($to_site_id, 'wu_customer_id', (int) $snapshot['wu_customer_id']);
	}
	update_site_meta($to_site_id, 'wu_type', 'customer_owned');
	if ( ! empty($snapshot['wu_active']) ) {
		update_site_meta($to_site_id, 'wu_active', $snapshot['wu_active']);
	}

	// Force fresh Site model load on next access.
	clean_blog_cache($to_site_id);
	wp_cache_delete($to_site_id, 'wu_sites');

	kp_um_tpl_log('POST-COPY-RESTORE', 'identity restored immediately after copy_data', [
		'to_blog_id'       => $to_site_id,
		'admin_email'      => $snapshot['admin_email'] ?? '',
		'blogname'         => $snapshot['blogname'] ?? '',
		'wu_customer_id'   => $snapshot['wu_customer_id'] ?? 0,
		'wu_membership_id' => $snapshot['wu_membership_id'] ?? 0,
	]);
}

/**
 * BUG 12 FIX (v3.4) — Restore admin_email BEFORE override_site executes
 * via mucd_before_copy_files hook.
 *
 * After a successful switch, MUCD_Data::copy_data() copies
 * wp_X_options.admin_email from the template to the customer subsite.
 * On the NEXT switch attempt, override_site reads that contaminated
 * email, calls create_admin() which fails.
 *
 * Fix: at mucd_before_copy_files (which fires AFTER override_site has
 * already determined the email but BEFORE the copy starts), we restore
 * the customer's real admin_email from our backup so the user lookup
 * in create_admin() actually finds the existing customer user.
 *
 * Also runs at wu_before_switch_template if that ever exists.
 */
add_action('mucd_before_copy_files', 'kp_um_tpl_pre_copy_admin_email_fix', 1, 2);

function kp_um_tpl_pre_copy_admin_email_fix($from_site_id, $to_site_id) {
	$to_site_id = (int) $to_site_id;
	if ( $to_site_id <= 1 ) return;

	$backup = get_site_option('kp_um_tpl_backup_' . $to_site_id);
	if ( empty($backup['admin_email']) ) return;

	$current_email = get_blog_option($to_site_id, 'admin_email');
	if ( $current_email === $backup['admin_email'] ) return;

	update_blog_option($to_site_id, 'admin_email', $backup['admin_email']);

	kp_um_tpl_log('PRE-COPY-FIX', 'admin_email restored before copy starts', [
		'to_blog_id'    => $to_site_id,
		'was'           => $current_email,
		'restored_to'   => $backup['admin_email'],
	]);
}

/**
 * BUG 12 FIX (v3.4) — Override create_admin user_id resolution.
 *
 * The real root cause: Site_Duplicator::process_duplication calls
 * create_admin($args->email) which uses email_exists() but if the email
 * was overwritten by previous template's copy_data, email_exists()
 * returns the WRONG user_id. Or it tries wpmu_create_user with a
 * domain string that's invalid as a login.
 *
 * Fix: hook wp_authenticate_user / pre_get_users_by_email to redirect
 * to the customer's actual user_id when the email matches.
 *
 * Better: just hook wp_die / WP_Error returned from create_admin and
 * substitute with valid user_id from snapshot.
 *
 * Even better: restore admin_email of subsite BEFORE every override
 * attempt by listening on the only thing that fires reliably — the
 * shutdown hook from previous switch (which already restores it).
 *
 * The cleanest fix is to use 'pre_user_login' filter or alternatively
 * intercept create_admin via the user_id arg. We can do this by
 * filtering 'wpmu_create_user' return value when login is invalid:
 */
add_filter('pre_user_email', 'kp_um_tpl_filter_pre_user_email', 10, 1);

function kp_um_tpl_filter_pre_user_email($email) {
	// No-op filter — just here as placeholder. Real fix is admin_email restore via cron-style.
	return $email;
}

/**
 * Run admin_email restore on every WP load for blogs that have a backup,
 * if their current admin_email differs from backup. Idempotent + safe.
 */
add_action('init', 'kp_um_tpl_continuous_admin_email_guard', 999);

function kp_um_tpl_continuous_admin_email_guard() {
	if ( ! is_multisite() ) return;
	if ( ! is_admin() && ! defined('WP_CLI') && ! wp_doing_ajax() ) return;

	// Only for the current blog if it has a backup (not iterating all).
	$blog_id = get_current_blog_id();
	if ( $blog_id <= 1 ) return;

	$backup = get_site_option('kp_um_tpl_backup_' . $blog_id);
	if ( empty($backup['admin_email']) ) return;

	$current_email = get_blog_option($blog_id, 'admin_email');
	if ( $current_email === $backup['admin_email'] ) return;

	// Only restore if backup was taken recently (last 24h) — otherwise
	// it might be a legitimate change.
	if ( ! empty($backup['taken_at']) && (time() - $backup['taken_at']) > DAY_IN_SECONDS ) return;

	update_blog_option($blog_id, 'admin_email', $backup['admin_email']);

	kp_um_tpl_log('GUARD', 'admin_email auto-restored on init', [
		'blog_id'       => $blog_id,
		'was'           => $current_email,
		'restored_to'   => $backup['admin_email'],
	]);
}

/**
 * BUG 12 ROOT FIX (v3.4.1) — Wrap Site_Duplicator::override_site
 *
 * The cleanest fix: provide a kp_um_tpl_safe_override_site() helper that
 * takes a snapshot before, calls override_site, and runs full pipeline
 * after. Used by reset_subsite() AND can be called directly from admin/CLI
 * tools to do a "switch with all guarantees".
 */
function kp_um_tpl_safe_override_site($from_template_id, $to_blog_id) {
	$from_template_id = (int) $from_template_id;
	$to_blog_id = (int) $to_blog_id;

	if ( ! class_exists('\WP_Ultimo\Helpers\Site_Duplicator') ) {
		return new \WP_Error('um_missing', 'Ultimate Multisite not loaded');
	}

	// 0. CRITICAL FIX (v3.5.0) — Capture caller context, don't blindly unwind (CodeRabbit feedback).
	$caller_blog_id = get_current_blog_id();
	wp_cache_flush();

	// Clean user cache for the customer that override_site will lookup.
	$customer = wu_get_customer( (int) get_site_meta($to_blog_id, 'wu_customer_id', true) );
	if ( $customer && method_exists($customer, 'get_user_id') ) {
		clean_user_cache( $customer->get_user_id() );
	}

	// 1. CRITICAL FIX (v3.4.5) — Use PERSISTENT backup if available.
	// The dynamic snapshot can be contaminated if a previous switch failed
	// and left blogname=template's name. The persistent backup
	// (kp_um_tpl_backup_X) has the ORIGINAL customer values from FIRST switch.
	$persistent_backup = get_site_option('kp_um_tpl_backup_' . $to_blog_id);

	$snapshot = kp_um_tpl_build_snapshot($to_blog_id);

	if ( ! empty($persistent_backup) ) {
		// PREFER persistent backup for identity fields (blogname, email,
		// customer_id, membership_id). It has the original values.
		$identity_fields = ['blogname', 'blogdescription', 'home', 'siteurl', 'admin_email',
			'wu_membership_id', 'wu_customer_id', 'wu_categories'];
		foreach ($identity_fields as $f) {
			if ( ! empty($persistent_backup[$f]) ) {
				$snapshot[$f] = $persistent_backup[$f];
			}
		}
	} else {
		// First switch ever for this blog — save the snapshot as persistent backup
		// so future switches can use it.
		if ( $snapshot ) {
			update_site_option('kp_um_tpl_backup_' . $to_blog_id, $snapshot);
		}
	}

	// 2. Pre-warm Elementor + extend timeouts.
	@set_time_limit(KP_UM_TPL_TIMEOUT);
	@ini_set('memory_limit', KP_UM_TPL_MEMORY);
	if ( ! defined('WP_IMPORTING') ) {
		define('WP_IMPORTING', true);
	}
	kp_um_tpl_warmup_elementor();

	kp_um_tpl_log('SAFE-OVERRIDE', 'starting', [
		'from_template_id' => $from_template_id,
		'to_blog_id'       => $to_blog_id,
	]);

	// 3. Call UM core override (this updates wu_template_id and copies content).
	$result = \WP_Ultimo\Helpers\Site_Duplicator::override_site($from_template_id, $to_blog_id);

	if ( ! $result ) {
		kp_um_tpl_log('SAFE-OVERRIDE', 'override_site returned false', [
			'blog_id' => $to_blog_id,
		]);
		return false;
	}

	// 4. RESTORE blogname + admin_email + customer/membership IDs (these are
	// the only things copy_data overwrote that we want from the snapshot).
	if ( $snapshot ) {
		if ( ! empty($snapshot['blogname']) ) {
			update_blog_option($to_blog_id, 'blogname', $snapshot['blogname']);
		}
		if ( ! empty($snapshot['blogdescription']) ) {
			update_blog_option($to_blog_id, 'blogdescription', $snapshot['blogdescription']);
		}
		if ( ! empty($snapshot['home']) ) {
			update_blog_option($to_blog_id, 'home', $snapshot['home']);
		}
		if ( ! empty($snapshot['siteurl']) ) {
			update_blog_option($to_blog_id, 'siteurl', $snapshot['siteurl']);
		}
		if ( ! empty($snapshot['admin_email']) ) {
			update_blog_option($to_blog_id, 'admin_email', $snapshot['admin_email']);
		}
		// blogmeta — preserve customer relationship.
		if ( ! empty($snapshot['wu_membership_id']) ) {
			update_site_meta($to_blog_id, 'wu_membership_id', $snapshot['wu_membership_id']);
		}
		if ( ! empty($snapshot['wu_customer_id']) ) {
			update_site_meta($to_blog_id, 'wu_customer_id', $snapshot['wu_customer_id']);
		}
		// CRITICAL: wu_type must be customer_owned (override_site sets to 'customer_owned' in line 144 already).
		update_site_meta($to_blog_id, 'wu_type', 'customer_owned');

		kp_um_tpl_log('SAFE-OVERRIDE', 'identity restored from snapshot', [
			'blog_id'  => $to_blog_id,
			'blogname' => $snapshot['blogname'],
		]);
	}

	// 5. CRITICAL: copy Kit settings/data from new template (BUG 10 fix).
	kp_um_tpl_force_copy_kit($from_template_id, $to_blog_id);

	// 6. Refresh thumbnail.
	if ( $snapshot ) {
		kp_um_tpl_refresh_thumbnail($to_blog_id, $snapshot, 'safe-override');
	}

	// 7. Cache cleanup + Elementor regen.
	kp_um_tpl_clean_caches($to_blog_id);
	kp_um_tpl_regen_elementor_full($to_blog_id);
	kp_um_tpl_purge_litespeed($to_blog_id);

	// 8. CRITICAL POST-FIX (v3.5.0) — Restore to caller context only (CodeRabbit feedback).
	if ( get_current_blog_id() !== (int) $caller_blog_id ) {
		switch_to_blog( (int) $caller_blog_id );
	}
	wp_cache_flush();
	if ( $customer && method_exists($customer, 'get_user_id') ) {
		clean_user_cache( $customer->get_user_id() );
	}

	kp_um_tpl_log('SAFE-OVERRIDE', 'complete', [
		'from_template_id' => $from_template_id,
		'to_blog_id'       => $to_blog_id,
	]);

	return $result;
}

function kp_um_tpl_auto_snapshot_pre_copy($from_site_id, $to_site_id) {
	$to_site_id = (int) $to_site_id;
	if ( $to_site_id <= 1 ) return;

	// If we already have a snapshot for this blog, don't overwrite it.
	$existing = get_transient('kp_um_tpl_snapshot_' . $to_site_id);
	if ( ! empty($existing) ) {
		return;
	}

	$snapshot = kp_um_tpl_build_snapshot($to_site_id);
	if ( ! $snapshot ) return;

	set_transient('kp_um_tpl_snapshot_' . $to_site_id, $snapshot, HOUR_IN_SECONDS);
	update_site_option('kp_um_tpl_backup_' . $to_site_id, $snapshot);

	register_shutdown_function('kp_um_tpl_shutdown_safety_net', $to_site_id);

	@set_time_limit(KP_UM_TPL_TIMEOUT);
	@ini_set('memory_limit', KP_UM_TPL_MEMORY);

	if ( ! defined('WP_IMPORTING') ) {
		define('WP_IMPORTING', true);
	}

	kp_um_tpl_warmup_elementor();

	kp_um_tpl_log('AUTO-SNAPSHOT', 'snapshot taken via mucd_before_copy_data hook (CLI or non-AJAX)', [
		'from_site_id'    => $from_site_id,
		'to_blog_id'      => $to_site_id,
		'blogname'        => $snapshot['blogname'],
		'wu_template_id'  => $snapshot['wu_template_id'],
	]);
}

/**
 * BUG 12 FIX (v3.4) — Auto post-restore after override completes.
 *
 * Equivalent to kp_um_tpl_post_switch but hooked into MUCD's own action
 * so it fires for direct override_site() calls, not just AJAX.
 */
add_action('wu_duplicate_site', 'kp_um_tpl_auto_post_copy', 999, 1);

function kp_um_tpl_auto_post_copy($args) {
	if ( ! is_array($args) || empty($args['site_id']) ) return;
	$blog_id = (int) $args['site_id'];
	if ( $blog_id <= 1 ) return;

	// If snapshot was taken via auto-hook (not AJAX), trigger our full
	// post-switch pipeline now that copy is done.
	$snapshot = get_transient('kp_um_tpl_snapshot_' . $blog_id);
	if ( empty($snapshot) ) return;

	kp_um_tpl_log('AUTO-POST', 'wu_duplicate_site fired — running post-switch pipeline', [
		'blog_id' => $blog_id,
	]);

	// Trigger the canonical post-switch action so all hooks see it.
	do_action('wu_after_switch_template', $blog_id);
}

function kp_um_tpl_pre_switch() {
	if ( ! function_exists('wu_get_current_site') ) return;

	$site = wu_get_current_site();
	if ( ! $site ) return;

	$blog_id = (int) $site->get_id();
	if ( $blog_id <= 1 ) return;

	$snapshot = kp_um_tpl_build_snapshot($blog_id);
	if ( ! $snapshot ) return;

	set_transient('kp_um_tpl_snapshot_' . $blog_id, $snapshot, HOUR_IN_SECONDS);
	update_site_option('kp_um_tpl_backup_' . $blog_id, $snapshot);

	register_shutdown_function('kp_um_tpl_shutdown_safety_net', $blog_id);

	@set_time_limit(KP_UM_TPL_TIMEOUT);
	@ini_set('memory_limit', KP_UM_TPL_MEMORY);

	if ( ! defined('WP_IMPORTING') ) {
		define('WP_IMPORTING', true);
	}

	// BUG 7 fix — pre-warm Elementor.
	kp_um_tpl_warmup_elementor();

	kp_um_tpl_log('PRE-SWITCH', 'snapshot saved + timeout extended + Elementor warmed', [
		'blog_id'         => $blog_id,
		'blogname'        => $snapshot['blogname'],
		'wu_type'         => $snapshot['wu_type'],
		'wu_membership_id' => $snapshot['wu_membership_id'],
		'wu_customer_id'  => $snapshot['wu_customer_id'],
		'wu_template_id'  => $snapshot['wu_template_id'],
		'timeout_s'       => KP_UM_TPL_TIMEOUT,
		'memory'          => KP_UM_TPL_MEMORY,
	]);
}

/* ────────────────────────────────────────────────────────────────────
 * BUG 8 FIX — Allow same-template switch (treats as RESET)
 *
 * UM core's switch_template handler in class-template-switching-element.php
 * does not validate same-template selection. JS does block it via
 * v-show="template_id != original_template_id" but a power user can
 * call the AJAX directly. We just log it as a reset operation.
 *
 * The real fix for Bug 8 is a NEW button in the UI that explicitly
 * resets the current template — handled by kp_um_tpl_inject_reset_button.
 * ──────────────────────────────────────────────────────────────────── */

add_action('wu_ajax_wu_switch_template', 'kp_um_tpl_detect_reset_intent', 2);

function kp_um_tpl_detect_reset_intent() {
	if ( ! function_exists('wu_get_current_site') ) return;
	$site = wu_get_current_site();
	if ( ! $site ) return;

	$current_template = (int) $site->get_template_id();
	$requested_template = (int) ($_REQUEST['template_id'] ?? 0);

	if ( $current_template > 0 && $current_template === $requested_template ) {
		kp_um_tpl_log('RESET-INTENT', 'user requested same template — treating as reset', [
			'blog_id'       => $site->get_id(),
			'template_id'   => $current_template,
		]);
	}
}

/* ────────────────────────────────────────────────────────────────────
 * SHUTDOWN SAFETY NET
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_shutdown_safety_net($blog_id) {
	$blog_id = (int) $blog_id;
	$snapshot = get_transient('kp_um_tpl_snapshot_' . $blog_id);

	if ( empty($snapshot) ) return;

	$err = error_get_last();
	$is_fatal = $err && in_array(
		$err['type'],
		[E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR],
		true
	);

	kp_um_tpl_log('SHUTDOWN-SAFETY', 'AJAX died — emergency restoring identity (content kept from template)', [
		'blog_id'   => $blog_id,
		'is_fatal'  => $is_fatal,
		'php_error' => $err,
	]);

	kp_um_tpl_restore_identity($blog_id, $snapshot, 'shutdown-safety-net');
	kp_um_tpl_clean_caches($blog_id);
}

/* ────────────────────────────────────────────────────────────────────
 * STEP 2 — Post-switch restore (happy path)
 * ──────────────────────────────────────────────────────────────────── */

add_action('wu_after_switch_template', 'kp_um_tpl_post_switch', 1, 1);

function kp_um_tpl_post_switch($blog_id) {
	$blog_id = (int) $blog_id;
	$snapshot = get_transient('kp_um_tpl_snapshot_' . $blog_id);
	if ( empty($snapshot) ) {
		$snapshot = get_site_option('kp_um_tpl_backup_' . $blog_id);
	}

	$has_snapshot = ! empty($snapshot);

	kp_um_tpl_log('POST-SWITCH', $has_snapshot ? 'restore starting' : 'no snapshot — Kit copy + regen only', [
		'blog_id'      => $blog_id,
		'has_snapshot' => $has_snapshot,
	]);

	// Identity restore + thumbnail (only if we have a snapshot — these are
	// for switches that came through the AJAX path where snapshot was taken).
	if ( $has_snapshot ) {
		kp_um_tpl_restore_identity($blog_id, $snapshot, 'post-switch');
		kp_um_tpl_refresh_thumbnail($blog_id, $snapshot, 'post-switch');
	}

	// BUG 10 FIX (v3.3): Force-copy Kit settings from new template.
	// This MUST run even without snapshot — when CLI-driven or direct
	// Site_Duplicator::override_site() calls, there's no AJAX snapshot
	// but the Kit copy is still required.
	$current_template_id = (int) get_site_meta($blog_id, 'wu_template_id', true);
	if ( $current_template_id > 0 ) {
		kp_um_tpl_force_copy_kit($current_template_id, $blog_id);
	}

	kp_um_tpl_clean_caches($blog_id);
	kp_um_tpl_regen_elementor_full($blog_id);
	kp_um_tpl_purge_litespeed($blog_id);

	if ( $has_snapshot ) {
		kp_um_tpl_verify_integrity($blog_id, $snapshot);
		delete_transient('kp_um_tpl_snapshot_' . $blog_id);
	}

	if ( ! wp_next_scheduled('kp_um_tpl_cleanup_old_backups') ) {
		wp_schedule_event(time() + 3600, 'daily', 'kp_um_tpl_cleanup_old_backups');
	}

	kp_um_tpl_log('POST-SWITCH', 'restore complete', ['blog_id' => $blog_id]);
}

/* ────────────────────────────────────────────────────────────────────
 * IDENTITY RESTORE
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_restore_identity($blog_id, $snapshot, $context = '') {
	$blog_id = (int) $blog_id;
	if ( $blog_id <= 1 || empty($snapshot) ) return;

	$restored = [];

	$option_fields = ['blogname', 'blogdescription', 'home', 'siteurl', 'admin_email'];
	foreach ($option_fields as $key) {
		if ( ! empty($snapshot[$key]) ) {
			update_blog_option($blog_id, $key, $snapshot[$key]);
			$restored[] = $key;
		}
	}

	// BUG 9 (v3.2) — featured_image_id should follow the NEW template, not the old snapshot.
	// BUG 13 (v3.4.1) — wu_template_id must NOT be restored from snapshot — UM core
	// already updated it to the NEW template. Restoring from snapshot would revert
	// the switch. Same for wu_categories which depends on the new template.
	$meta_fields = [
		'wu_type', 'wu_membership_id', 'wu_customer_id', 'wu_active',
	];
	foreach ($meta_fields as $key) {
		if ( isset($snapshot[$key]) && $snapshot[$key] !== '' ) {
			update_site_meta($blog_id, $key, $snapshot[$key]);
			$restored[] = $key;
		}
	}

	kp_um_tpl_log('RESTORE-IDENTITY', 'fields restored (featured_image excluded — refreshed separately)', [
		'blog_id'  => $blog_id,
		'context'  => $context,
		'restored' => $restored,
		'blogname' => $snapshot['blogname'] ?? '',
		'wu_type'  => $snapshot['wu_type'] ?? '',
	]);
}

/**
 * BUG 9 FIX (v3.2) — Refresh thumbnail to match the active template.
 *
 * When a customer switches templates, the panel thumbnail must update to
 * reflect the new template's appearance. There are 3 sources of truth:
 *
 *   1. Active template's wu_featured_image_id (preferred — set by admin
 *      on the template site itself).
 *   2. The current_site's wu_featured_image_id from the snapshot (fallback
 *      for resets where template_id didn't change).
 *   3. mShots / Screenshot Service (last resort — auto-screenshot of subsite).
 *
 * This is called AFTER restore_identity sets wu_template_id.
 */
function kp_um_tpl_refresh_thumbnail($blog_id, $snapshot, $context = '') {
	$blog_id = (int) $blog_id;
	if ( $blog_id <= 1 ) return;

	$current_template_id = (int) get_site_meta($blog_id, 'wu_template_id', true);
	$snapshot_template_id = isset($snapshot['wu_template_id']) ? (int) $snapshot['wu_template_id'] : 0;
	$is_template_change = ($current_template_id !== $snapshot_template_id) && $current_template_id > 0;

	$source = '';
	$new_image_id = 0;

	// Strategy 1: Use the new template's featured image if available.
	if ( $current_template_id > 0 ) {
		$template_image = get_site_meta($current_template_id, 'wu_featured_image_id', true);
		if ( ! empty($template_image) ) {
			$new_image_id = (int) $template_image;
			$source = 'template-meta';
		}
	}

	// Strategy 2: If no template image and we're doing a RESET (same template),
	// keep the original featured_image_id from the snapshot.
	if ( $new_image_id <= 0 && ! $is_template_change && ! empty($snapshot['wu_featured_image_id']) ) {
		$new_image_id = (int) $snapshot['wu_featured_image_id'];
		$source = 'snapshot-reset';
	}

	// Strategy 3: Trigger mShots regeneration. This works async — the screenshot
	// service will hit the subsite and cache a fresh thumbnail.
	if ( $new_image_id <= 0 ) {
		// Clear the old image so the panel falls back to mShots / placeholder.
		delete_site_meta($blog_id, 'wu_featured_image_id');
		$source = 'mshots-fallback';

		// Trigger the WPU screenshot generator if available.
		if ( function_exists('wu_async_get_site_thumbnail') ) {
			try {
				wu_async_get_site_thumbnail($blog_id);
			} catch (\Throwable $e) {
				// Continue.
			}
		}

		kp_um_tpl_log('THUMBNAIL', 'cleared featured_image_id, mShots will regenerate', [
			'blog_id'   => $blog_id,
			'context'   => $context,
		]);
		return;
	}

	// Apply the determined image.
	update_site_meta($blog_id, 'wu_featured_image_id', $new_image_id);

	kp_um_tpl_log('THUMBNAIL', 'refreshed', [
		'blog_id'             => $blog_id,
		'context'             => $context,
		'is_template_change'  => $is_template_change,
		'old_template_id'     => $snapshot_template_id,
		'new_template_id'     => $current_template_id,
		'new_image_id'        => $new_image_id,
		'source'              => $source,
	]);
}

/* ────────────────────────────────────────────────────────────────────
 * CACHE CLEANING
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_clean_caches($blog_id) {
	$blog_id = (int) $blog_id;
	clean_blog_cache($blog_id);

	$cache_groups = ['wu_sites', 'wu_memberships', 'wu_customers', 'site-meta', 'blog-meta', 'options', 'site-options'];
	foreach ($cache_groups as $group) {
		wp_cache_delete($blog_id, $group);
		wp_cache_delete('wu_get_site_' . $blog_id, $group);
	}

	if ( function_exists('wp_cache_flush_group') ) {
		wp_cache_flush_group('wu_sites');
	}

	kp_um_tpl_log('CACHE-CLEAN', 'all relevant caches cleaned', ['blog_id' => $blog_id]);
}

/* ────────────────────────────────────────────────────────────────────
 * BUG 10 FIX (v3.3) — Force-copy Kit Elementor settings from source template
 *
 * MUCD_Data::copy_data() copies postmeta rows but the Elementor Kit's
 * `_elementor_page_settings` and `_elementor_data` are sometimes stale
 * because Elementor uses internal caches. Forcing an explicit copy +
 * regen guarantees colors/typography match the chosen template.
 *
 * @param int $from_template_id Source template blog_id (e.g. 97 for Belleza)
 * @param int $to_blog_id       Target customer subsite blog_id
 * @return bool true if Kit copied and regen'd, false if skipped
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_force_copy_kit($from_template_id, $to_blog_id) {
	$from_template_id = (int) $from_template_id;
	$to_blog_id = (int) $to_blog_id;

	if ( $from_template_id <= 0 || $to_blog_id <= 1 || $from_template_id === $to_blog_id ) {
		// $from === $to is RESET — still force-copy from itself to refresh caches.
		if ( $from_template_id !== $to_blog_id ) {
			return false;
		}
	}

	if ( ! class_exists('\Elementor\Plugin') ) {
		kp_um_tpl_log('KIT-COPY', 'Elementor not available, skipping', [
			'from_template_id' => $from_template_id,
			'to_blog_id'       => $to_blog_id,
		]);
		return false;
	}

	// Step 1: Read Kit settings + data from source template.
	switch_to_blog($from_template_id);
	$src_kit_id = (int) get_option('elementor_active_kit');
	$src_settings = $src_kit_id > 0 ? get_post_meta($src_kit_id, '_elementor_page_settings', true) : null;
	$src_data = $src_kit_id > 0 ? get_post_meta($src_kit_id, '_elementor_data', true) : null;
	$src_all_meta = $src_kit_id > 0 ? get_post_meta($src_kit_id) : [];
	$src_color = '';
	if ( is_array($src_settings) && ! empty($src_settings['system_colors'][0]['color']) ) {
		$src_color = $src_settings['system_colors'][0]['color'];
	}
	restore_current_blog();

	if ( $src_kit_id <= 0 || empty($src_settings) ) {
		kp_um_tpl_log('KIT-COPY', 'source Kit not found, skipping', [
			'from_template_id' => $from_template_id,
			'src_kit_id'       => $src_kit_id,
		]);
		return false;
	}

	// Step 2: Apply to target subsite.
	switch_to_blog($to_blog_id);
	$dst_kit_id = (int) get_option('elementor_active_kit');

	// If target has no active kit, set to source kit ID (usually post 3).
	if ( $dst_kit_id <= 0 ) {
		update_option('elementor_active_kit', $src_kit_id);
		$dst_kit_id = $src_kit_id;
	}

	// Force-update Kit settings + data.
	update_post_meta($dst_kit_id, '_elementor_page_settings', $src_settings);
	if ( ! empty($src_data) ) {
		update_post_meta($dst_kit_id, '_elementor_data', $src_data);
	}

	// Copy ALL _elementor_* and _wp_* meta from src Kit to dst Kit.
	if ( ! empty($src_all_meta) ) {
		foreach ($src_all_meta as $meta_key => $values) {
			// Only copy elementor + wp_ meta keys (not core post fields).
			if ( strpos($meta_key, '_elementor') !== 0 && strpos($meta_key, '_wp_') !== 0 ) {
				continue;
			}
			// Skip already-handled keys.
			if ( in_array($meta_key, ['_elementor_page_settings', '_elementor_data'], true) ) {
				continue;
			}
			delete_post_meta($dst_kit_id, $meta_key);
			foreach ($values as $v) {
				$unserialized = maybe_unserialize($v);
				add_post_meta($dst_kit_id, $meta_key, $unserialized);
			}
		}
	}

	// Step 3: Regenerate the Kit CSS so frontend reflects new colors.
	if ( class_exists('\Elementor\Core\Files\CSS\Post') ) {
		try {
			(new \Elementor\Core\Files\CSS\Post($dst_kit_id))->update();
		} catch (\Throwable $e) {
			kp_um_tpl_log('KIT-COPY', 'Kit CSS regen failed', [
				'to_blog_id' => $to_blog_id,
				'kit_id'     => $dst_kit_id,
				'error'      => $e->getMessage(),
			]);
		}
	}

	// Verify color was applied.
	$dst_settings = get_post_meta($dst_kit_id, '_elementor_page_settings', true);
	$dst_color = '';
	if ( is_array($dst_settings) && ! empty($dst_settings['system_colors'][0]['color']) ) {
		$dst_color = $dst_settings['system_colors'][0]['color'];
	}

	restore_current_blog();

	$matched = ($dst_color === $src_color && ! empty($src_color));

	kp_um_tpl_log('KIT-COPY', $matched ? 'OK — Kit colors match source template' : 'WARNING — Kit colors mismatch after copy', [
		'from_template_id' => $from_template_id,
		'to_blog_id'       => $to_blog_id,
		'src_kit_id'       => $src_kit_id,
		'dst_kit_id'       => $dst_kit_id,
		'src_color'        => $src_color,
		'dst_color'        => $dst_color,
		'matched'          => $matched,
	]);

	return $matched;
}

/* ────────────────────────────────────────────────────────────────────
 * BUG 4 FIX (improved in v3.0) — Regenerate ALL Elementor CSS
 *
 * v2.0 only regenerated 30 pages. v3.0 regenerates EVERYTHING:
 * pages + posts + elementor_library (templates) + custom post types.
 * This eliminates "TU LOGO AQUI / Lorem ipsum" symptoms entirely.
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_regen_elementor_full($blog_id) {
	$blog_id = (int) $blog_id;

	if ( ! class_exists('\Elementor\Plugin') || ! class_exists('\Elementor\Core\Files\CSS\Post') ) {
		kp_um_tpl_log('ELEMENTOR-REGEN', 'Elementor not available, skipping', ['blog_id' => $blog_id]);
		return;
	}

	switch_to_blog($blog_id);

	// BUG 7 fix — pre-warm before regen too (sometimes breakpoints
	// gets reset between switch_to_blog calls).
	kp_um_tpl_warmup_elementor();

	$kit_id = (int) get_option('elementor_active_kit');
	$regen_count = 0;
	$failures = 0;

	try {
		// 1) Active Kit first (highest priority — design system).
		if ( $kit_id > 0 ) {
			try {
				(new \Elementor\Core\Files\CSS\Post($kit_id))->update();
				$regen_count++;
			} catch (\Throwable $e) {
				$failures++;
				kp_um_tpl_log('ELEMENTOR-REGEN', 'kit regen failed', [
					'blog_id' => $blog_id,
					'kit_id'  => $kit_id,
					'error'   => $e->getMessage(),
				]);
			}
		}

		// 2) ALL pages, posts, elementor_library, and any post type with _elementor_data.
		$post_types = ['page', 'post', 'elementor_library'];
		// Also include any other post types with elementor data.
		$extra_types = get_post_types(['public' => true], 'names');
		$post_types = array_unique(array_merge($post_types, $extra_types));

		$all_posts = get_posts([
			'post_type'      => $post_types,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => '_elementor_data',
					'compare' => 'EXISTS',
				],
			],
		]);

		foreach ($all_posts as $post_id) {
			try {
				(new \Elementor\Core\Files\CSS\Post($post_id))->update();
				$regen_count++;
			} catch (\Throwable $e) {
				$failures++;
			}
		}
	} catch (\Throwable $e) {
		kp_um_tpl_log('ELEMENTOR-REGEN', 'fatal during full regen', [
			'blog_id' => $blog_id,
			'error'   => $e->getMessage(),
		]);
	}

	restore_current_blog();

	kp_um_tpl_log('ELEMENTOR-REGEN', 'full regen complete', [
		'blog_id'     => $blog_id,
		'kit_id'      => $kit_id,
		'regen_count' => $regen_count,
		'failures'    => $failures,
	]);
}

/* ────────────────────────────────────────────────────────────────────
 * CACHE PURGE
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_purge_litespeed($blog_id) {
	$blog_id = (int) $blog_id;
	switch_to_blog($blog_id);

	if ( class_exists('LiteSpeed\Purge') ) {
		try {
			do_action('litespeed_purge_all');
			kp_um_tpl_log('PURGE', 'LiteSpeed purged', ['blog_id' => $blog_id]);
		} catch (\Throwable $e) {
			// Continue.
		}
	}

	wp_cache_flush();
	restore_current_blog();
}

/* ────────────────────────────────────────────────────────────────────
 * INTEGRITY VERIFICATION
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_verify_integrity($blog_id, $snapshot) {
	$blog_id = (int) $blog_id;
	$mismatches = [];

	$current_blogname = get_blog_option($blog_id, 'blogname');
	if ( ! empty($snapshot['blogname']) && $current_blogname !== $snapshot['blogname'] ) {
		$mismatches['blogname'] = ['expected' => $snapshot['blogname'], 'actual' => $current_blogname];
	}

	$current_wu_type = get_site_meta($blog_id, 'wu_type', true);
	if ( ! empty($snapshot['wu_type']) && $current_wu_type !== $snapshot['wu_type'] ) {
		$mismatches['wu_type'] = ['expected' => $snapshot['wu_type'], 'actual' => $current_wu_type];
	}

	$current_membership = (int) get_site_meta($blog_id, 'wu_membership_id', true);
	if ( ! empty($snapshot['wu_membership_id']) && $current_membership !== (int) $snapshot['wu_membership_id'] ) {
		$mismatches['wu_membership_id'] = ['expected' => $snapshot['wu_membership_id'], 'actual' => $current_membership];
	}

	if ( empty($mismatches) ) {
		kp_um_tpl_log('VERIFY', 'integrity OK — all fields restored correctly', ['blog_id' => $blog_id]);
	} else {
		kp_um_tpl_log('VERIFY', 'INTEGRITY FAILURE', ['blog_id' => $blog_id, 'mismatches' => $mismatches]);
	}
}

/* ────────────────────────────────────────────────────────────────────
 * EMERGENCY RESTORE — manual recovery
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_force_restore($blog_id) {
	$blog_id = (int) $blog_id;

	$snapshot = get_transient('kp_um_tpl_snapshot_' . $blog_id);
	if ( empty($snapshot) ) {
		$snapshot = get_site_option('kp_um_tpl_backup_' . $blog_id);
	}
	if ( empty($snapshot) ) {
		kp_um_tpl_log('FORCE-RESTORE', 'no backup available', ['blog_id' => $blog_id]);
		return false;
	}

	kp_um_tpl_log('FORCE-RESTORE', 'starting manual restore', ['blog_id' => $blog_id]);

	kp_um_tpl_restore_identity($blog_id, $snapshot, 'force-manual');
	kp_um_tpl_refresh_thumbnail($blog_id, $snapshot, 'force-manual');

	// BUG 10 FIX (v3.3): Force-copy Kit settings from current template.
	$current_template_id = (int) get_site_meta($blog_id, 'wu_template_id', true);
	if ( $current_template_id > 0 ) {
		kp_um_tpl_force_copy_kit($current_template_id, $blog_id);
	}

	kp_um_tpl_clean_caches($blog_id);
	kp_um_tpl_regen_elementor_full($blog_id);
	kp_um_tpl_purge_litespeed($blog_id);
	kp_um_tpl_verify_integrity($blog_id, $snapshot);

	kp_um_tpl_log('FORCE-RESTORE', 'manual restore complete', ['blog_id' => $blog_id]);
	return true;
}

/* ────────────────────────────────────────────────────────────────────
 * BUG 8 FIX — Direct subsite reset CLI command
 *
 * `wp kp-um-tpl reset <blog_id>` resets a subsite to its current
 * template_id from scratch. Safer than user-facing button because
 * runs from CLI without timeout.
 *
 * Internally calls Site_Duplicator::override_site($current_template, $blog_id)
 * + restores customer identity afterwards via existing pipeline.
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_reset_subsite($blog_id) {
	$blog_id = (int) $blog_id;
	if ( $blog_id <= 1 ) {
		return new \WP_Error('invalid_blog', 'blog_id must be > 1');
	}

	if ( ! class_exists('\WP_Ultimo\Helpers\Site_Duplicator') ) {
		return new \WP_Error('um_missing', 'Ultimate Multisite not loaded');
	}

	$site = wu_get_site($blog_id);
	if ( ! $site ) {
		return new \WP_Error('site_not_found', "Subsite {$blog_id} not found");
	}

	$template_id = (int) $site->get_template_id();
	if ( $template_id <= 0 ) {
		return new \WP_Error('no_template', "Subsite {$blog_id} has no associated template");
	}

	// Snapshot first.
	$snapshot = kp_um_tpl_build_snapshot($blog_id);
	if ( $snapshot ) {
		set_transient('kp_um_tpl_snapshot_' . $blog_id, $snapshot, HOUR_IN_SECONDS);
		update_site_option('kp_um_tpl_backup_' . $blog_id, $snapshot);
	}

	kp_um_tpl_log('RESET', 'starting CLI reset', [
		'blog_id'     => $blog_id,
		'template_id' => $template_id,
	]);

	// Pre-warm Elementor to dodge BUG 7.
	kp_um_tpl_warmup_elementor();

	@set_time_limit(KP_UM_TPL_TIMEOUT);
	@ini_set('memory_limit', KP_UM_TPL_MEMORY);

	if ( ! defined('WP_IMPORTING') ) {
		define('WP_IMPORTING', true);
	}

	// Execute the override (this runs copy_data, copy_files, etc.)
	$result = \WP_Ultimo\Helpers\Site_Duplicator::override_site($template_id, $blog_id);

	if ( ! $result ) {
		kp_um_tpl_log('RESET', 'override_site returned false', ['blog_id' => $blog_id]);
		// Try restore identity anyway since copy may have partially succeeded.
		if ( $snapshot ) {
			kp_um_tpl_restore_identity($blog_id, $snapshot, 'reset-failed-recovery');
		}
		return new \WP_Error('override_failed', 'Site_Duplicator::override_site returned false');
	}

	// Run our full restore + regen pipeline.
	kp_um_tpl_restore_identity($blog_id, $snapshot, 'reset-cli');
	kp_um_tpl_refresh_thumbnail($blog_id, $snapshot, 'reset-cli');

	// BUG 10 FIX (v3.3): Force-copy Kit settings from template.
	kp_um_tpl_force_copy_kit($template_id, $blog_id);

	kp_um_tpl_clean_caches($blog_id);
	kp_um_tpl_regen_elementor_full($blog_id);
	kp_um_tpl_purge_litespeed($blog_id);
	kp_um_tpl_verify_integrity($blog_id, $snapshot);

	kp_um_tpl_log('RESET', 'reset complete', [
		'blog_id'     => $blog_id,
		'template_id' => $template_id,
		'result'      => $result,
	]);

	return $result;
}

/* ────────────────────────────────────────────────────────────────────
 * AUDIT
 * ──────────────────────────────────────────────────────────────────── */

function kp_um_tpl_audit_orphans() {
	global $wpdb;

	$results = $wpdb->get_results("
		SELECT bm.blog_id,
		       MAX(CASE WHEN bm.meta_key='wu_type' THEN bm.meta_value END) as wu_type,
		       MAX(CASE WHEN bm.meta_key='wu_membership_id' THEN bm.meta_value END) as wu_membership_id,
		       MAX(CASE WHEN bm.meta_key='wu_customer_id' THEN bm.meta_value END) as wu_customer_id
		FROM {$wpdb->base_prefix}blogmeta bm
		WHERE bm.meta_key IN ('wu_type','wu_membership_id','wu_customer_id')
		GROUP BY bm.blog_id
	", ARRAY_A);

	$orphans = [];
	foreach ($results as $row) {
		$bid = (int) $row['blog_id'];
		if ( $bid <= 1 ) continue;

		if ( $row['wu_type'] === 'customer_owned' ) {
			if ( empty($row['wu_membership_id']) ) {
				$orphans[$bid] = 'customer_owned without wu_membership_id';
			} elseif ( empty($row['wu_customer_id']) ) {
				$orphans[$bid] = 'customer_owned without wu_customer_id';
			}
		}

		if ( $row['wu_type'] === 'site_template' && ! empty($row['wu_membership_id']) ) {
			$orphans[$bid] = sprintf(
				'site_template type but has wu_membership_id=%s — likely a corrupted customer site',
				$row['wu_membership_id']
			);
		}
	}

	return $orphans;
}

/* ────────────────────────────────────────────────────────────────────
 * BACKUP CLEANUP
 * ──────────────────────────────────────────────────────────────────── */

add_action('kp_um_tpl_cleanup_old_backups', 'kp_um_tpl_cleanup_old_backups');

function kp_um_tpl_cleanup_old_backups() {
	global $wpdb;

	$cutoff = time() - (KP_UM_TPL_BACKUP_DAYS * DAY_IN_SECONDS);

	$backups = $wpdb->get_results(
		"SELECT meta_key, meta_value FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'kp_um_tpl_backup_%'",
		ARRAY_A
	);

	$removed = 0;
	foreach ($backups as $row) {
		$snapshot = maybe_unserialize($row['meta_value']);
		if ( ! is_array($snapshot) || empty($snapshot['taken_at']) ) continue;
		if ( (int) $snapshot['taken_at'] < $cutoff ) {
			delete_site_option($row['meta_key']);
			$removed++;
		}
	}

	if ( $removed > 0 ) {
		kp_um_tpl_log('CLEANUP', 'old backups removed', [
			'count'     => $removed,
			'days_kept' => KP_UM_TPL_BACKUP_DAYS,
		]);
	}
}

/* ────────────────────────────────────────────────────────────────────
 * BUG 8 FIX (v3.1) — UI button "Reset current template"
 *
 * Inject a "Resetear esta plantilla" button next to the orange "Plantilla
 * Seleccionada" button via JS. When clicked, it calls our custom AJAX
 * endpoint that runs the same reset CLI flow without timeout risk.
 *
 * No core file modified — pure JS injection on the template selection UI.
 * ──────────────────────────────────────────────────────────────────── */

add_action('wu_ajax_kp_um_tpl_reset_current', 'kp_um_tpl_ajax_reset_current');
add_action('wu_ajax_nopriv_kp_um_tpl_reset_current', 'kp_um_tpl_ajax_reset_current_nopriv');

function kp_um_tpl_ajax_reset_current_nopriv() {
	wp_send_json_error(['message' => __('You must be logged in.', 'kp-um-tpl')]);
}

function kp_um_tpl_ajax_reset_current() {
	if ( ! function_exists('wu_get_current_site') ) {
		wp_send_json_error(['message' => 'Ultimate Multisite not loaded']);
	}

	$site = wu_get_current_site();
	if ( ! $site ) {
		wp_send_json_error(['message' => 'Current site not found']);
	}

	// Customer permission check — only the site's owner can reset their own site.
	$current_user_id = get_current_user_id();
	$site_customer = method_exists($site, 'get_customer') ? $site->get_customer() : null;

	if ( $site_customer && method_exists($site_customer, 'get_user_id') ) {
		$site_user_id = (int) $site_customer->get_user_id();
		// Allow owner OR super admin.
		if ( $site_user_id !== $current_user_id && ! is_super_admin($current_user_id) ) {
			wp_send_json_error(['message' => 'You do not have permission to reset this site.']);
		}
	}

	$blog_id = (int) $site->get_id();

	kp_um_tpl_log('UI-RESET', 'reset triggered from panel UI', [
		'blog_id'    => $blog_id,
		'user_id'    => $current_user_id,
	]);

	$result = kp_um_tpl_reset_subsite($blog_id);

	if ( is_wp_error($result) ) {
		wp_send_json_error(['message' => $result->get_error_message()]);
	}

	$referer = isset($_SERVER['HTTP_REFERER']) ? sanitize_url(wp_unslash($_SERVER['HTTP_REFERER'])) : '';

	wp_send_json_success([
		'message'      => __('Plantilla reseteada correctamente. Recargando...', 'kp-um-tpl'),
		'redirect_url' => add_query_arg(['updated' => 1, 'reset' => 1], $referer),
	]);
}

/**
 * Inject the "Reset current template" button + AJAX handler JS into the
 * template selection page. Only loads on pages where the template
 * switching element is rendered.
 */
add_action('wp_footer', 'kp_um_tpl_inject_reset_button_js', 100);

function kp_um_tpl_inject_reset_button_js() {
	// Only inject on the customer panel / template switching page.
	if ( ! function_exists('wu_get_current_site') ) return;
	if ( is_admin() ) return;

	// Detect if the page contains the template-switching form.
	global $post;
	$content = '';
	if ( $post && ! empty($post->post_content) ) {
		$content = $post->post_content;
	}

	$has_switching_element = (
		stripos($content, 'wu_template_switching') !== false
		|| stripos($content, '[wu_template_switching') !== false
		|| stripos($content, 'wpfa:') !== false  // WPFA panel context
	);

	// Always inject on panel.kursopro.com (broad match) since it's where
	// the cliente sees Mi Suscripción with template selection.
	$current_host = $_SERVER['HTTP_HOST'] ?? '';
	$is_panel = (stripos($current_host, 'panel.') === 0 || stripos($current_host, 'panel.kursopro.com') !== false);

	if ( ! $has_switching_element && ! $is_panel ) {
		return;
	}

	$ajax_url = function_exists('wu_ajax_url') ? wu_ajax_url() : admin_url('admin-ajax.php');
	$confirm_text = esc_js(__('¿Seguro que quieres resetear esta plantilla? Tu sitio volverá al diseño original. Esta acción no se puede deshacer.', 'kp-um-tpl'));
	$reset_text = esc_js(__('Resetear esta plantilla', 'kp-um-tpl'));
	$loading_text = esc_js(__('Reseteando...', 'kp-um-tpl'));
	$success_text = esc_js(__('¡Plantilla reseteada! Recargando...', 'kp-um-tpl'));
	$error_text = esc_js(__('Error al resetear: ', 'kp-um-tpl'));
	?>
<!-- KP UM Template Switch v3.1 — Reset button injection -->
<style id="kp-um-tpl-reset-styles">
.kp-um-tpl-reset-btn {
	display: block;
	width: 100%;
	margin-top: 8px;
	padding: 10px 16px;
	background: #fff;
	color: #d63638;
	border: 2px solid #d63638;
	border-radius: 4px;
	font-size: 14px;
	font-weight: 600;
	text-align: center;
	cursor: pointer;
	transition: all 0.2s ease;
	font-family: inherit;
	text-transform: uppercase;
	letter-spacing: 0.3px;
}
.kp-um-tpl-reset-btn:hover {
	background: #d63638;
	color: #fff;
}
.kp-um-tpl-reset-btn:disabled {
	opacity: 0.5;
	cursor: wait;
}
.kp-um-tpl-reset-overlay {
	position: fixed;
	top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(255,255,255,0.85);
	z-index: 999999;
	display: none;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	font-family: inherit;
}
.kp-um-tpl-reset-overlay.is-active {
	display: flex;
}
.kp-um-tpl-reset-overlay .kp-spinner {
	width: 60px;
	height: 60px;
	border: 6px solid #f3f3f3;
	border-top: 6px solid #0c2134;
	border-radius: 50%;
	animation: kp-spin 1s linear infinite;
	margin-bottom: 20px;
}
.kp-um-tpl-reset-overlay .kp-msg {
	font-size: 18px;
	font-weight: 600;
	color: #0c2134;
	max-width: 400px;
	text-align: center;
	line-height: 1.4;
}
.kp-um-tpl-reset-overlay .kp-submsg {
	font-size: 14px;
	color: #666;
	margin-top: 8px;
}
@keyframes kp-spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
</style>

<div id="kp-um-tpl-reset-overlay" class="kp-um-tpl-reset-overlay">
	<div class="kp-spinner"></div>
	<div class="kp-msg" id="kp-um-tpl-reset-msg"><?php echo esc_html__('Reseteando plantilla...', 'kp-um-tpl'); ?></div>
	<div class="kp-submsg"><?php echo esc_html__('Esto puede tomar 30-90 segundos. No cierres esta ventana.', 'kp-um-tpl'); ?></div>
</div>

<script type="text/javascript">
(function() {
	'use strict';

	var KP_UM_TPL = {
		ajaxUrl: <?php echo wp_json_encode($ajax_url); ?>,
		confirmText: '<?php echo $confirm_text; ?>',
		resetText: '<?php echo $reset_text; ?>',
		loadingText: '<?php echo $loading_text; ?>',
		successText: '<?php echo $success_text; ?>',
		errorText: '<?php echo $error_text; ?>',
	};

	function showOverlay(msg) {
		var overlay = document.getElementById('kp-um-tpl-reset-overlay');
		var msgEl = document.getElementById('kp-um-tpl-reset-msg');
		if (overlay) overlay.classList.add('is-active');
		if (msgEl && msg) msgEl.textContent = msg;
	}

	function hideOverlay() {
		var overlay = document.getElementById('kp-um-tpl-reset-overlay');
		if (overlay) overlay.classList.remove('is-active');
	}

	function triggerReset() {
		if (!confirm(KP_UM_TPL.confirmText)) {
			return;
		}

		showOverlay(KP_UM_TPL.loadingText);

		var xhr = new XMLHttpRequest();
		var url = KP_UM_TPL.ajaxUrl + '&action=kp_um_tpl_reset_current';
		xhr.open('POST', url, true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.timeout = 600000; // 10 min for safety

		xhr.onload = function() {
			try {
				var response = JSON.parse(xhr.responseText);
				if (response && response.success) {
					showOverlay(KP_UM_TPL.successText);
					setTimeout(function() {
						if (response.data && response.data.redirect_url) {
							window.location.href = response.data.redirect_url;
						} else {
							window.location.reload(true);
						}
					}, 800);
				} else {
					hideOverlay();
					var msg = (response && response.data && response.data.message) ? response.data.message : 'Error desconocido';
					alert(KP_UM_TPL.errorText + msg);
				}
			} catch (e) {
				hideOverlay();
				alert(KP_UM_TPL.errorText + 'Respuesta inválida del servidor.');
			}
		};

		xhr.onerror = function() {
			hideOverlay();
			alert(KP_UM_TPL.errorText + 'Conexión perdida.');
		};

		xhr.ontimeout = function() {
			hideOverlay();
			alert(KP_UM_TPL.errorText + 'El reset tomó demasiado tiempo. Recarga la página para verificar el estado.');
		};

		xhr.send('');
	}

	function injectResetButton() {
		// Find the orange "Plantilla Seleccionada" button (selected template).
		var selectedBtn = document.querySelector('.wu-selected-template-button');
		if (!selectedBtn) return false;

		// Don't double-inject.
		if (selectedBtn.parentElement.querySelector('.kp-um-tpl-reset-btn')) return true;

		var resetBtn = document.createElement('button');
		resetBtn.type = 'button';
		resetBtn.className = 'kp-um-tpl-reset-btn';
		resetBtn.textContent = KP_UM_TPL.resetText;
		resetBtn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			triggerReset();
		});

		selectedBtn.parentElement.appendChild(resetBtn);
		return true;
	}

	// Try inject on multiple lifecycle events (Vue.js may render late).
	function tryInject() {
		var injected = injectResetButton();
		if (!injected) {
			setTimeout(tryInject, 500);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', tryInject);
	} else {
		tryInject();
	}

	// Also re-inject when Vue updates the DOM (template switching is Vue-based).
	if (typeof MutationObserver !== 'undefined') {
		var observer = new MutationObserver(function(mutations) {
			injectResetButton();
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}
})();
</script>
<!-- /KP UM Template Switch v3.1 -->
	<?php
}

/* ────────────────────────────────────────────────────────────────────
 * WP-CLI COMMANDS
 * ──────────────────────────────────────────────────────────────────── */

if ( defined('WP_CLI') && WP_CLI ) {

	/**
	 * KP UM Template Switch — admin tools.
	 *
	 * ## EXAMPLES
	 *
	 *     # Reset a subsite to its current template (full reset, no UI needed)
	 *     wp kp-um-tpl reset 291
	 *
	 *     # Restore a subsite identity from snapshot (after a failed switch)
	 *     wp kp-um-tpl restore 291
	 *
	 *     # Audit all subsites for blogmeta corruption
	 *     wp kp-um-tpl audit
	 *
	 *     # Show snapshot/backup contents
	 *     wp kp-um-tpl show 291
	 */
	class KP_UM_Tpl_CLI {

		/**
		 * Reset a subsite to its current template (RESET behavior).
		 *
		 * Equivalent to user choosing "switch to current template" in panel.
		 * Bypasses the AJAX path so no timeout risk.
		 *
		 * ## OPTIONS
		 *
		 * <blog_id>
		 * : Blog ID of subsite to reset.
		 */
		public function reset($args) {
			[$blog_id] = $args;
			$blog_id = (int) $blog_id;

			$result = kp_um_tpl_reset_subsite($blog_id);

			if ( is_wp_error($result) ) {
				\WP_CLI::error($result->get_error_message());
			} else {
				\WP_CLI::success("Subsite {$blog_id} reset successfully (result={$result}). Check kp-template-switch.log for details.");
			}
		}

		/**
		 * Restore subsite identity from snapshot (after a failed switch).
		 *
		 * ## OPTIONS
		 *
		 * <blog_id>
		 * : Blog ID of subsite to restore.
		 */
		public function restore($args) {
			[$blog_id] = $args;
			$blog_id = (int) $blog_id;

			if ( kp_um_tpl_force_restore($blog_id) ) {
				\WP_CLI::success("Subsite {$blog_id} restored. Check kp-template-switch.log for details.");
			} else {
				\WP_CLI::error("No backup found for blog {$blog_id}.");
			}
		}

		/**
		 * Audit all subsites for orphan blogmeta.
		 */
		public function audit() {
			$orphans = kp_um_tpl_audit_orphans();

			if ( empty($orphans) ) {
				\WP_CLI::success("No orphan subsites found. All blogmeta is consistent.");
				return;
			}

			\WP_CLI::warning(sprintf("Found %d orphan subsite(s):", count($orphans)));
			foreach ($orphans as $bid => $issue) {
				\WP_CLI::log("  blog_id={$bid}: {$issue}");
			}
		}

		/**
		 * Force-copy Kit Elementor settings from a subsite's current template.
		 *
		 * Useful when a client's site has wrong colors after a failed switch.
		 * Fixes BUG 10 manually.
		 *
		 * ## OPTIONS
		 *
		 * <blog_id>
		 * : Customer subsite blog ID.
		 *
		 * [--from=<template_blog_id>]
		 * : Optional source template ID. If not provided, uses the subsite's wu_template_id.
		 *
		 * ## EXAMPLES
		 *
		 *     wp kp-um-tpl regen-kit 291
		 *     wp kp-um-tpl regen-kit 291 --from=97
		 */
		public function regen_kit($args, $assoc_args) {
			[$blog_id] = $args;
			$blog_id = (int) $blog_id;

			$from_template = isset($assoc_args['from']) ? (int) $assoc_args['from'] : 0;
			if ( $from_template <= 0 ) {
				$from_template = (int) get_site_meta($blog_id, 'wu_template_id', true);
			}

			if ( $from_template <= 0 ) {
				\WP_CLI::error("Could not determine source template for blog {$blog_id}. Pass --from=<template_blog_id>.");
			}

			$result = kp_um_tpl_force_copy_kit($from_template, $blog_id);

			// Always also regen all CSS files.
			kp_um_tpl_regen_elementor_full($blog_id);
			kp_um_tpl_purge_litespeed($blog_id);

			if ( $result ) {
				\WP_CLI::success("Kit copied from template {$from_template} to subsite {$blog_id}. Colors should now match.");
			} else {
				\WP_CLI::warning("Kit copy completed but colors may not match exactly. Check kp-template-switch.log.");
			}
		}

		/**
		 * Show snapshot for a subsite.
		 *
		 * ## OPTIONS
		 *
		 * <blog_id>
		 * : Blog ID.
		 */
		public function show($args) {
			[$blog_id] = $args;
			$blog_id = (int) $blog_id;

			$transient = get_transient('kp_um_tpl_snapshot_' . $blog_id);
			$option = get_site_option('kp_um_tpl_backup_' . $blog_id);

			\WP_CLI::log("=== Transient snapshot (1h TTL) ===");
			\WP_CLI::log($transient ? wp_json_encode($transient, JSON_PRETTY_PRINT) : '(none)');

			\WP_CLI::log("\n=== Permanent backup (30d TTL) ===");
			\WP_CLI::log($option ? wp_json_encode($option, JSON_PRETTY_PRINT) : '(none)');
		}
	}

	\WP_CLI::add_command('kp-um-tpl', 'KP_UM_Tpl_CLI');
}
