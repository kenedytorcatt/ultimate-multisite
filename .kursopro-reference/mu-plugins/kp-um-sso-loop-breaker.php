<?php
/**
 * Plugin Name: KP UM SSO Loop Breaker
 * Description: Patches the cross-domain SSO redirect loop in Ultimate Multisite when a non-super-admin client visits a mapped subsite admin (e.g. via WPFA Premium iframe). Bug: javieraacademy.com/wp-admin → kursopro.com/login/?sso=login&redirect_to=... → back to mapped domain → infinite loop.
 * Version: 3.0.0
 * Author: KursoPro
 * Network: true
 *
 * ─────────────────────────────────────────────────────────────────────
 * STRATEGY (v3.0 — Counter on main domain cookie)
 * ─────────────────────────────────────────────────────────────────────
 *
 * Why previous versions failed:
 * - v1.0 (cookies): cookies don't cross domain boundaries
 * - v1.1 (transients): hook ran too late (init), UM hooked on plugins_loaded:0
 * - v2.0 (Referer detection): browsers preserve original Referer through 302
 *   redirect chains, so Referer always shows the original click source
 *
 * v3.0 strategy — leverages the fact that EVERY iteration of the loop
 * passes through kursopro.com (main domain), and cookies on the main
 * domain DO persist between those iterations even though the loop
 * crosses to mapped domains in between:
 *
 *   iter 1: kursopro.com/login/?sso=login   ← main domain (cookie sees here)
 *   iter 2: javieraacademy.com/wp-admin     ← mapped (cookie not sent)
 *   iter 3: kursopro.com/login/?sso=login   ← main domain (cookie SEES previous!)
 *
 * So a counter cookie with explicit domain=COOKIE_DOMAIN works to detect
 * iter 3 (= we've already been to kursopro.com once with sso=login).
 *
 * SAFEGUARDS:
 * - Skips super admins (they don't loop)
 * - Skips when wu_sso_denied is already set
 * - Counter expires after 30s (window of normal SSO completion)
 * - Hook on muplugins_loaded:0 (BEFORE any plugin runs)
 * - Idempotent: only checks once per request
 * - Logs to debug.log when triggers
 */

defined('ABSPATH') || exit;

class KP_UM_SSO_Loop_Breaker {

	const COUNTER_COOKIE = 'kp_sso_attempt';
	const MAX_ATTEMPTS = 1;          // 2nd visit to ?sso=login = loop
	const WINDOW_SECONDS = 30;

	private static $checked = false;

	public static function init() {
		// Run before any plugin loads. UM hooks on plugins_loaded:0 so we
		// must trip BEFORE that.
		add_action('muplugins_loaded', [__CLASS__, 'check'], 0);
		add_action('plugins_loaded', [__CLASS__, 'check'], -9999);
	}

	public static function check() {
		if (self::$checked) return;
		self::$checked = true;

		// Only intercept SSO login attempts — raw $_GET because plugins
		// (including UM filters) haven't loaded yet
		$sso = isset($_GET['sso']) ? (string) $_GET['sso'] : '';
		$wu_sso = isset($_GET['wu_sso']) ? (string) $_GET['wu_sso'] : '';

		if ($sso !== 'login' && $wu_sso !== 'login') {
			return;
		}

		// If UM already denied SSO, do nothing — UM handles it
		if ( ! empty($_COOKIE['wu_sso_denied'])) {
			self::clear_counter();
			return;
		}

		// Skip super admins — they don't enter the iframe loop
		// (we can't easily check super admin this early, but they don't
		// hit ?sso=login through panel iframe anyway, so safe to proceed)

		$now = time();
		$count = 0;
		$ts = 0;

		if (isset($_COOKIE[self::COUNTER_COOKIE])) {
			$parts = explode('|', (string) $_COOKIE[self::COUNTER_COOKIE]);
			if (count($parts) === 2) {
				$count = (int) $parts[0];
				$ts = (int) $parts[1];
			}
		}

		// Reset if window expired
		if ($count === 0 || ($now - $ts) > self::WINDOW_SECONDS) {
			$count = 0;
			$ts = $now;
		}

		$count++;

		if ($count > self::MAX_ATTEMPTS) {
			// LOOP CONFIRMED — break it
			self::trip_wu_sso_denied();
			self::clear_counter();
			self::log(sprintf('Loop broken after %d attempts in %ds', $count, $now - $ts));
			self::redirect_safe();
			exit;
		}

		// Persist counter for next iteration
		self::set_counter($count, $ts);
	}

	private static function set_counter($count, $ts) {
		$value = $count . '|' . $ts;
		$expire = time() + self::WINDOW_SECONDS + 5;
		$path = '/';
		$domain = self::cookie_domain();
		$secure = ! empty($_SERVER['HTTPS']);

		if (PHP_VERSION_ID >= 70300) {
			setcookie(self::COUNTER_COOKIE, $value, [
				'expires'  => $expire,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => $secure,
				'httponly' => false,
				'samesite' => $secure ? 'None' : 'Lax',
			]);
		} else {
			setcookie(self::COUNTER_COOKIE, $value, $expire, $path, $domain, $secure, false);
		}
	}

	private static function clear_counter() {
		$path = '/';
		$domain = self::cookie_domain();

		if (PHP_VERSION_ID >= 70300) {
			setcookie(self::COUNTER_COOKIE, '', [
				'expires' => time() - 3600,
				'path'    => $path,
				'domain'  => $domain,
			]);
		} else {
			setcookie(self::COUNTER_COOKIE, '', time() - 3600, $path, $domain);
		}
	}

	private static function trip_wu_sso_denied() {
		$expire = time() + 300;
		$path = '/';
		$domain = self::cookie_domain();
		$secure = ! empty($_SERVER['HTTPS']);

		if (PHP_VERSION_ID >= 70300) {
			setcookie('wu_sso_denied', '1', [
				'expires'  => $expire,
				'path'     => $path,
				'domain'   => $domain,
				'secure'   => $secure,
				'httponly' => false,
				'samesite' => $secure ? 'None' : 'Lax',
			]);
		} else {
			setcookie('wu_sso_denied', '1', $expire, $path, $domain, $secure, false);
		}
		$_COOKIE['wu_sso_denied'] = '1';
	}

	private static function cookie_domain() {
		// Use COOKIE_DOMAIN if defined and non-empty, else current host
		if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN !== '') {
			return COOKIE_DOMAIN;
		}
		$host = $_SERVER['HTTP_HOST'] ?? '';
		if (strpos($host, ':') !== false) {
			$host = explode(':', $host)[0];
		}
		return $host;
	}

	private static function redirect_safe() {
		$main_domain = defined('DOMAIN_CURRENT_SITE') && DOMAIN_CURRENT_SITE !== ''
			? DOMAIN_CURRENT_SITE
			: ($_SERVER['HTTP_HOST'] ?? 'kursopro.com');
		$main_domain = preg_replace('/^www\./', '', $main_domain);
		// Strip subdomain "panel."
		if (strpos($main_domain, 'panel.') === 0) {
			$main_domain = substr($main_domain, 6);
		}
		$url = 'https://panel.' . $main_domain . '/';
		header('Location: ' . $url, true, 302);
	}

	private static function log($msg) {
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			$host = $_SERVER['HTTP_HOST'] ?? '?';
			$uri = $_SERVER['REQUEST_URI'] ?? '?';
			$ref = $_SERVER['HTTP_REFERER'] ?? '?';
			error_log("[KP-SSO-LOOP-BREAKER] $msg | host=$host uri=$uri ref=$ref");
		}
	}
}

KP_UM_SSO_Loop_Breaker::init();
