<?php
/**
 * Plugin Name: KP SSO Mapped Domain Fix
 * Description: Fix admin bar SSO en dominios mapeados (WP Ultimo 2.9.2 PR #366 regression).
 * Version: 1.1.0
 * Author: KursoPro
 *
 * PROBLEMA: WP Ultimo 2.9.2 (PR #366) cambió el SSO para que JSONP NO
 * haga attach via redirect (causaba loop). Resultado: la barra de admin
 * nunca aparece en frontend de mapped domain en primera visita.
 *
 * SOLUCIÓN: Inyectar un <script> ANTES del sso.min.js que añade un
 * fallback al callback `wu.sso`: si recibe "Broker not attached" Y
 * detectamos que el visitante PUDO haber hecho login (no hay cookie
 * `kp_sso_tried`), navegamos a /sso?return_url=ACTUAL via window.location.
 * Eso ejecuta el attach via redirect chain del navegador (que SÍ sigue 302
 * porque no es <script>) y vuelve. Marcamos `kp_sso_tried` para evitar loop.
 */

defined('ABSPATH') || exit;

// Kill switch: define KP_SSO_MAPPED_FIX_DISABLE=true en wp-config para desactivar.
if (defined('KP_SSO_MAPPED_FIX_DISABLE') && KP_SSO_MAPPED_FIX_DISABLE) {
    return;
}

// Modo SAFE: solo activo si KP_SSO_MAPPED_FIX_ENABLE está definido (default: OFF).
// Esto da un kill-switch inverso: el plugin existe pero NO hace nada hasta activarlo.
if (!defined('KP_SSO_MAPPED_FIX_ENABLE') || !KP_SSO_MAPPED_FIX_ENABLE) {
    return;
}

add_action('wp_head', function () {
    if (is_admin() || is_user_logged_in()) {
        return;
    }
    if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('REST_REQUEST')) {
        return;
    }

    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    if ($host === '' || str_ends_with($host, '.kursopro.com') || $host === 'kursopro.com') {
        return;
    }

    if (!empty($_COOKIE['kp_sso_attached']) || !empty($_COOKIE['wu_sso_denied']) || !empty($_COOKIE['kp_sso_tried'])) {
        return;
    }

    if (apply_filters('kp_sso_skip_for_bot', false)) {
        return;
    }

    $req_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if (str_contains($req_uri, 'sso_verify') || str_contains($req_uri, '/sso?') || str_contains($req_uri, 'kp_sso_done')) {
        return;
    }

    ?>
<script id="kp-sso-mapped-fix">
(function(){
  if (window.__kpSsoFixInstalled) return;
  window.__kpSsoFixInstalled = true;

  function setCookie(n, v, secs) {
    var d = new Date(); d.setTime(d.getTime() + secs * 1000);
    document.cookie = n + '=' + v + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
  }
  function hasCookie(n) {
    return document.cookie.split(';').some(function(c){ return c.trim().indexOf(n + '=') === 0; });
  }

  if (!window.wu) window.wu = {};
  var origSso = window.wu.sso;

  window.wu.sso = function(payload, status) {
    try {
      if (payload && payload.code === 0 && payload.message === 'Broker not attached') {
        if (!hasCookie('kp_sso_tried') && !hasCookie('kp_sso_attached')) {
          setCookie('kp_sso_tried', '1', 1800);
          var ret = window.location.href.split('#')[0];
          var sep = ret.indexOf('?') === -1 ? '?' : '&';
          var done = ret + sep + 'kp_sso_done=1';
          window.location.replace('/sso?return_url=' + encodeURIComponent(done));
          return;
        }
      }
    } catch(e) {}
    if (typeof origSso === 'function') return origSso.apply(this, arguments);
  };
})();
</script>
    <?php
}, 0);

add_action('wp_loaded', function () {
    $req_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if (!str_contains($req_uri, 'kp_sso_done')) {
        return;
    }
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
    if ($host === '') {
        return;
    }
    if (is_user_logged_in()) {
        setcookie('kp_sso_attached', '1', time() + 3600, '/', $host, true, false);
        setcookie('kp_sso_tried', '', time() - 3600, '/', $host, true, false);
    }

    // Limpiar el query arg de la URL (cosmético, evita kp_sso_done=1 visible).
    $clean = preg_replace('/([?&])kp_sso_done=1(&|$)/', '$1', $req_uri);
    $clean = rtrim((string) $clean, '?&');
    if ($clean === '') {
        $clean = '/';
    }
    if ($clean !== $req_uri) {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        nocache_headers();
        wp_safe_redirect($scheme . $host . $clean, 302, 'KP-SSO-Cleanup');
        exit;
    }
});

// Skip para bots conocidos: NO disparamos JS de redirect.
add_filter('kp_sso_skip_for_bot', function () {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower((string) $_SERVER['HTTP_USER_AGENT']) : '';
    if ($ua === '') {
        return true;
    }
    $bots = ['bot', 'crawler', 'spider', 'curl', 'wget', 'facebookexternal', 'whatsapp', 'telegram', 'slack', 'preview', 'monitor', 'pingdom', 'uptimerobot', 'gtmetrix', 'pagespeed'];
    foreach ($bots as $b) {
        if (str_contains($ua, $b)) {
            return true;
        }
    }
    return false;
});
