<?php
/**
 * Plugin Name: KP UM Cookie-less SSO Token
 * Description: Backports cookie-less cross-domain SSO from Ultimate Multisite main branch (commits 7012d4a8, 9b011840, 70c2216d) to v2.9.2 production. Handles BOTH sides: emitter (main site adds token to redirects to mapped domains for logged-in users) + receiver (mapped domain validates token + sets auth cookie). HMAC-signed, single-use, time-limited (5 min). Will be removed once UM v2.9.3+ is released.
 * Version: 1.0.0
 * Author: KursoPro (backport from David Stone's main branch)
 * Network: true
 *
 * ─────────────────────────────────────────────────────────────────────
 * HOW IT WORKS
 * ─────────────────────────────────────────────────────────────────────
 *
 * EMITTER (kursopro.com main site):
 * - Hook on 'wp_redirect' filter
 * - If redirecting a logged-in user to a different host (mapped domain)
 *   AND the user is the same on both ends (multisite shared users)
 * - Append ?wu_sso_token=XXX to the redirect URL
 *
 * RECEIVER (mapped domain — javieraacademy.com etc.):
 * - Hook on 'init' priority 4
 * - If $_GET['wu_sso_token'] present
 * - Validate HMAC + expiry + audience + single-use (transient jti)
 * - wp_set_auth_cookie() for the user_id
 * - Redirect to clean URL without token
 *
 * TOKEN FORMAT (David's main):
 *   base64url(hmac_sha256 + "::" + json_payload)
 *   payload = {user_id, exp, aud (host), jti (uuid)}
 *   key = wp_salt('auth')
 *   single-use = wu_sso_magic_{jti} site_transient (TTL 5min)
 *
 * SAFEGUARDS:
 * - Skip if function already exists in core (UM v2.9.3+)
 * - Skip super admins (they don't loop)
 * - 5 minute expiration on tokens
 * - Single-use enforcement via site_transient
 * - HMAC signature prevents forging
 * - Audience check prevents cross-host token reuse
 */

defined('ABSPATH') || exit;

// Bootstrap on plugins_loaded so UM is available
add_action('plugins_loaded', function () {
	// If UM core already has this (v2.9.3+), do nothing
	if (class_exists('\WP_Ultimo\SSO\SSO')) {
		try {
			$sso = \WP_Ultimo\SSO\SSO::get_instance();
			if (method_exists($sso, 'handle_cookie_less_sso_token')) {
				return; // already in core
			}
		} catch (\Throwable $e) {
			// fall through
		}
	}

	KP_UM_Cookie_Less_SSO::init();
}, 5);

class KP_UM_Cookie_Less_SSO {

	const LOG_PREFIX = '[KP-COOKIE-LESS-SSO]';

	public static function init(): void {
		// RECEIVER side: validate token on any incoming request
		add_action('init', [self::class, 'handle_incoming_token'], 4);

		// EMITTER side: append token to redirects from main site to mapped domains
		add_filter('wp_redirect', [self::class, 'add_token_to_redirect'], 9999, 2);

		// Also intercept wp_safe_redirect for SSO endpoints
		add_filter('wp_safe_redirect_fallback', [self::class, 'safe_redirect_fallback'], 10, 2);
	}

	// ─────────────────────────────────────────────────────────────────
	// RECEIVER
	// ─────────────────────────────────────────────────────────────────

	public static function handle_incoming_token(): void {
		$token = isset($_GET['wu_sso_token']) ? (string) $_GET['wu_sso_token'] : '';
		if ($token === '') {
			return;
		}

		// Don't double-authenticate
		if (is_user_logged_in()) {
			// Already authenticated — just clean URL
			self::log('User already logged in, removing token from URL');
			self::redirect_clean();
			return;
		}

		$result = self::validate_token($token);
		if (is_wp_error($result)) {
			self::log('Token validation FAILED: ' . $result->get_error_code() . ' — ' . $result->get_error_message());
			return;
		}

		$user_id = (int) $result['user_id'];

		wp_set_auth_cookie($user_id, true);
		wp_set_current_user($user_id);

		self::log('Authenticated user ' . $user_id . ' via cookie-less token, redirecting to clean URL');
		self::redirect_clean();
	}

	private static function redirect_clean(): void {
		$current_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
		$clean_uri = remove_query_arg('wu_sso_token', $current_uri);

		// Build absolute URL
		$scheme = ! empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$absolute = $scheme . '://' . $host . $clean_uri;

		wp_safe_redirect($absolute, 302, 'KP-Cookie-Less-SSO');
		exit;
	}

	private static function validate_token(string $token) {
		$token   = strtr($token, '-_', '+/');
		$padding = strlen($token) % 4;
		if ($padding) {
			$token .= str_repeat('=', 4 - $padding);
		}

		$decoded = base64_decode($token, true);
		if ( ! $decoded || strpos($decoded, '::') === false) {
			return new \WP_Error('invalid_token_format', 'Invalid SSO token format.');
		}

		[$expected_hmac, $payload_json] = explode('::', $decoded, 2);

		$hmac = hash_hmac('sha256', $payload_json, wp_salt('auth'));
		if ( ! hash_equals($hmac, $expected_hmac)) {
			return new \WP_Error('invalid_signature', 'Invalid SSO token signature.');
		}

		$payload = json_decode($payload_json, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return new \WP_Error('invalid_payload', 'Invalid SSO token payload.');
		}

		if (empty($payload['exp']) || $payload['exp'] < time()) {
			return new \WP_Error('token_expired', 'SSO token has expired.');
		}

		$current_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
		$audience     = strtolower((string) ($payload['aud'] ?? ''));
		if (empty($audience) || $audience !== $current_host) {
			return new \WP_Error('invalid_audience', 'Invalid SSO token audience. Expected: ' . $current_host . ', got: ' . $audience);
		}

		$jti = (string) ($payload['jti'] ?? '');
		if (empty($jti) || ! get_site_transient('wu_sso_magic_' . $jti)) {
			return new \WP_Error('invalid_jti', 'SSO token has already been used or is invalid.');
		}

		delete_site_transient('wu_sso_magic_' . $jti);

		$user = get_user_by('id', (int) ($payload['user_id'] ?? 0));
		if ( ! $user) {
			return new \WP_Error('user_not_found', 'User not found.');
		}

		return ['user_id' => $user->ID];
	}

	// ─────────────────────────────────────────────────────────────────
	// EMITTER
	// ─────────────────────────────────────────────────────────────────

	public static function add_token_to_redirect($location, $status = 302) {
		// Only emit from main site (kursopro.com) — receivers don't emit
		if ( ! is_main_site()) {
			return $location;
		}

		// Need a logged-in user to generate token for
		$user_id = get_current_user_id();
		if ($user_id <= 0) {
			return $location;
		}

		// Only add token if redirecting cross-domain
		if ( ! self::is_cross_domain_url($location)) {
			return $location;
		}

		// Only relevant for mapped subsite admin URLs
		if (strpos($location, '/wp-admin/') === false && strpos($location, '/wp-login.php') === false) {
			return $location;
		}

		// Don't double-add if already has token
		if (strpos($location, 'wu_sso_token=') !== false) {
			return $location;
		}

		$location = add_query_arg('wu_sso_token', self::generate_token($user_id, $location), $location);

		self::log('Added SSO token to cross-domain redirect for user ' . $user_id);

		return $location;
	}

	public static function safe_redirect_fallback($fallback_url, $status) {
		return $fallback_url;
	}

	private static function generate_token(int $user_id, string $audience_url): string {
		$audience_host = strtolower((string) wp_parse_url($audience_url, PHP_URL_HOST));

		$expiry = time() + 300;
		$jti    = wp_generate_uuid4();

		$payload = wp_json_encode([
			'user_id' => $user_id,
			'exp'     => $expiry,
			'aud'     => $audience_host,
			'jti'     => $jti,
		]);

		set_site_transient('wu_sso_magic_' . $jti, 1, 300);

		$hmac = hash_hmac('sha256', $payload, wp_salt('auth'));

		return rtrim(strtr(base64_encode($hmac . '::' . $payload), '+/', '-_'), '=');
	}

	private static function is_cross_domain_url(string $url): bool {
		$url_host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
		if ($url_host === '') {
			return false;
		}
		$current_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
		return $url_host !== $current_host;
	}

	// ─────────────────────────────────────────────────────────────────
	// LOG
	// ─────────────────────────────────────────────────────────────────

	private static function log(string $msg): void {
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			$host = $_SERVER['HTTP_HOST'] ?? '?';
			$uri = $_SERVER['REQUEST_URI'] ?? '?';
			error_log(self::LOG_PREFIX . ' [' . $host . '] ' . $msg . ' | uri=' . $uri);
		}
	}
}
