<?php
/**
 * Plugin Name: KP UM Cookieless SSO
 * Description: Backports cookie-less cross-domain SSO from Ultimate Multisite main branch (commits 7012d4a8 + 9b011840 + 70c2216d) to v2.9.2 production. Implements emitter (main site adds wu_sso_token to mapped-domain redirects when user is already logged in) + receiver (mapped domain validates token + sets auth cookie on init priority 4). Required because Chrome blocks 3rd-party cookies in iframes — this is the only way clients with mapped domains can authenticate into their subsite admin without re-typing password. Will be auto-disabled when UM v2.9.3+ ships with this code in core.
 * Version: 1.0.1
 * Author: KursoPro (backport from David Stone's main branch)
 * Network: true
 *
 * ─────────────────────────────────────────────────────────────────────
 * EXACT BACKPORT FROM UM MAIN class-sso.php LINES 589-770
 * ─────────────────────────────────────────────────────────────────────
 *
 * RECEIVER (mapped subsite — javieraacademy.com etc):
 *   add_action('init', [...], 4)
 *   - if ?wu_sso_token= present, validate HMAC + exp + audience + jti
 *   - if valid: wp_set_auth_cookie(user_id), redirect to clean URL
 *
 * EMITTER (main site kursopro.com):
 *   add_filter('login_redirect', [...], 20, 3)
 *   - if user logged in and redirect_to is mapped domain: add wu_sso_token to URL
 *
 * ALSO emitter for the iframe SSO path:
 *   add_action('init', 'handle_already_logged_in_on_login_page', 1)
 *   - if user logged in + visiting kursopro.com/login/?sso=login + redirect_to=mapped:
 *     generate token + 302 to mapped domain with wu_sso_token
 *
 * Token format (HMAC-signed, single-use, 5 min expiry):
 *   base64url(hmac_sha256 + "::" + json_payload)
 *   payload = {user_id, exp, aud (host), jti (uuid)}
 *   key = wp_salt('auth')
 *   single-use = wu_sso_magic_{jti} site_transient (TTL 5min)
 */

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
	// If UM core already has this (v2.9.3+), do nothing
	if (class_exists('\WP_Ultimo\SSO\SSO')) {
		try {
			$sso = \WP_Ultimo\SSO\SSO::get_instance();
			if (method_exists($sso, 'handle_cookie_less_sso_token')) {
				return;
			}
		} catch (\Throwable $e) { /* fall through */ }
	}

	KP_UM_Cookieless_SSO::init();
}, 5);

class KP_UM_Cookieless_SSO {

	const LOG_PREFIX = '[KP-COOKIELESS-SSO]';

	public static function init(): void {
		// RECEIVER: validate ?wu_sso_token= on incoming requests
		add_action('init', [self::class, 'handle_incoming_token'], 4);

		// EMITTER (path A): when user is already logged in and visits
		// kursopro.com/login/?sso=login&redirect_to=mapped — generate
		// token and redirect immediately
		add_action('init', [self::class, 'emit_token_when_already_logged_in'], 5);

		// EMITTER (path B): after a fresh login on main site, attach token
		// to the redirect URL pointing at a mapped domain
		add_filter('login_redirect', [self::class, 'attach_token_to_login_redirect'], 20, 3);
	}

	// ─────────────────────────────────────────────────────────────────
	// RECEIVER
	// ─────────────────────────────────────────────────────────────────

	public static function handle_incoming_token(): void {
		// Skip AJAX, REST, CRON
		if (defined('DOING_AJAX') && DOING_AJAX) return;
		if (defined('REST_REQUEST') && REST_REQUEST) return;
		if (defined('DOING_CRON') && DOING_CRON) return;
		if (defined('WP_CLI') && WP_CLI) return;

		$token = isset($_GET['wu_sso_token']) ? (string) $_GET['wu_sso_token'] : '';
		if ($token === '') {
			return;
		}

		// Already authenticated — just clean the URL
		if (is_user_logged_in()) {
			self::redirect_clean_url();
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

		self::log('Authenticated user ' . $user_id . ' on host ' . self::current_host());
		self::redirect_clean_url();
	}

	private static function redirect_clean_url(): void {
		$current_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
		$clean_uri = remove_query_arg(['wu_sso_token'], $current_uri);

		$scheme = ! empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$absolute = $scheme . '://' . $host . $clean_uri;

		wp_safe_redirect($absolute, 302, 'KP-Cookieless-SSO');
		exit;
	}

	// ─────────────────────────────────────────────────────────────────
	// EMITTER — path A: ?sso=login&redirect_to=mapped on main site
	// ─────────────────────────────────────────────────────────────────

	public static function emit_token_when_already_logged_in(): void {
		// Only on main site
		if ( ! is_main_site()) {
			return;
		}

		// Skip AJAX, REST, CRON to avoid interfering with panel internal calls
		if (defined('DOING_AJAX') && DOING_AJAX) return;
		if (defined('REST_REQUEST') && REST_REQUEST) return;
		if (defined('DOING_CRON') && DOING_CRON) return;
		if (defined('WP_CLI') && WP_CLI) return;

		// Only when user is already logged in
		if ( ! is_user_logged_in()) {
			return;
		}

		// Only when this is an SSO login request
		$sso = isset($_GET['sso']) ? (string) $_GET['sso'] : '';
		$wu_sso = isset($_GET['wu_sso']) ? (string) $_GET['wu_sso'] : '';
		if ($sso !== 'login' && $wu_sso !== 'login') {
			return;
		}

		// We need a redirect_to pointing to a different host
		$redirect_to = self::extract_redirect_to();
		if ($redirect_to === '' || ! self::is_cross_domain_url($redirect_to)) {
			return;
		}

		// Avoid reentry: if redirect_to already has wu_sso_token, let it through
		if (strpos($redirect_to, 'wu_sso_token=') !== false) {
			return;
		}

		// Avoid reentry: don't fire if the request itself already had a token
		if ( ! empty($_GET['wu_sso_token'])) {
			return;
		}

		$user_id = get_current_user_id();
		$token = self::generate_token($user_id, $redirect_to);
		$final_url = add_query_arg('wu_sso_token', $token, $redirect_to);

		self::log('Emitting token for user ' . $user_id . ' to ' . self::host_of($redirect_to));

		wp_safe_redirect($final_url, 302, 'KP-Cookieless-SSO');
		exit;
	}

	private static function extract_redirect_to(): string {
		$candidate = isset($_GET['redirect_to']) ? (string) $_GET['redirect_to'] : '';
		if ($candidate === '') {
			$candidate = isset($_GET['return_url']) ? (string) $_GET['return_url'] : '';
		}
		if ($candidate === '') {
			return '';
		}
		// URLs sometimes arrive double-encoded
		if (strpos($candidate, '%3A%2F%2F') !== false) {
			$candidate = urldecode($candidate);
		}
		return $candidate;
	}

	// ─────────────────────────────────────────────────────────────────
	// EMITTER — path B: post-login redirect via filter
	// ─────────────────────────────────────────────────────────────────

	public static function attach_token_to_login_redirect($redirect_to, $requested_redirect_to, $user) {
		if ( ! $user || is_wp_error($user)) {
			return $redirect_to;
		}

		// Only emit from main site
		if ( ! is_main_site()) {
			return $redirect_to;
		}

		// Pick the most useful candidate URL
		$candidate = $requested_redirect_to;
		if (empty($candidate) || ! is_string($candidate)) {
			$candidate = $redirect_to;
		}
		if (empty($candidate) || ! is_string($candidate)) {
			return $redirect_to;
		}

		if ( ! self::is_cross_domain_url($candidate)) {
			return $redirect_to;
		}

		if (strpos($candidate, 'wu_sso_token=') !== false) {
			return $candidate;
		}

		$token = self::generate_token((int) $user->ID, $candidate);
		$final_url = add_query_arg('wu_sso_token', $token, $candidate);

		self::log('Attached token to login_redirect for user ' . $user->ID . ' to ' . self::host_of($candidate));

		return $final_url;
	}

	// ─────────────────────────────────────────────────────────────────
	// TOKEN: generate + validate
	// ─────────────────────────────────────────────────────────────────

	private static function generate_token(int $user_id, string $audience_url): string {
		$audience_host = strtolower((string) wp_parse_url($audience_url, PHP_URL_HOST));

		$expiry = time() + 300;
		$jti = wp_generate_uuid4();

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

	private static function validate_token(string $token) {
		$token   = strtr($token, '-_', '+/');
		$padding = strlen($token) % 4;
		if ($padding) {
			$token .= str_repeat('=', 4 - $padding);
		}

		$decoded = base64_decode($token, true);
		if ( ! $decoded || strpos($decoded, '::') === false) {
			return new \WP_Error('invalid_format', 'Invalid SSO token format.');
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

		$current_host = self::current_host();
		$audience = strtolower((string) ($payload['aud'] ?? ''));
		if (empty($audience) || $audience !== $current_host) {
			return new \WP_Error('invalid_audience', 'Audience mismatch. Expected: ' . $current_host . ', got: ' . $audience);
		}

		$jti = (string) ($payload['jti'] ?? '');
		if (empty($jti) || ! get_site_transient('wu_sso_magic_' . $jti)) {
			return new \WP_Error('invalid_jti', 'Token already used or invalid.');
		}
		delete_site_transient('wu_sso_magic_' . $jti);

		$user = get_user_by('id', (int) ($payload['user_id'] ?? 0));
		if ( ! $user) {
			return new \WP_Error('user_not_found', 'User not found.');
		}

		return ['user_id' => $user->ID];
	}

	// ─────────────────────────────────────────────────────────────────
	// HELPERS
	// ─────────────────────────────────────────────────────────────────

	private static function current_host(): string {
		return strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
	}

	private static function host_of(string $url): string {
		return strtolower((string) wp_parse_url($url, PHP_URL_HOST));
	}

	private static function is_cross_domain_url(string $url): bool {
		$host = self::host_of($url);
		if ($host === '') {
			return false;
		}
		return $host !== self::current_host();
	}

	private static function log(string $msg): void {
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			$host = $_SERVER['HTTP_HOST'] ?? '?';
			error_log(self::LOG_PREFIX . ' [' . $host . '] ' . $msg);
		}
	}
}
