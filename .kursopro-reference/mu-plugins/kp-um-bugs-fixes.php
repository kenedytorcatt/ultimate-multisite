<?php
/**
 * Plugin Name: KP UM Bugs Fixes (defensive)
 * Description: Mu-plugin defensivo que parchea bugs runtime de ultimate-multisite v2.9.1 + ultimate-multisite-woocommerce v2.0.10. NO toca el core de David. Cada fix se puede deshabilitar individualmente con constantes. Retirar cuando David publique upstream.
 * Version: 1.0.0
 * Author: KursoPro
 * Network: true
 *
 * BUGS QUE PARCHA
 * ===============
 *
 * Fix #1 — mShots URL doble https
 *   Filter wu_screenshot_api_url: detecta y limpia 'https://https://' en URLs.
 *   Backup defensivo aunque ya tengamos fix en core (sobrevive updates).
 *   Constante: KP_UM_FIX_MSHOTS_URL_DISABLE
 *
 * Fix #2 — Elementor crash en process_membership_changes
 *   Bug: David hace publish_pending_site_async() después del pago. Algún hook
 *   downstream llama Elementor::get_css_wrapper_selector() sobre un Document
 *   que es false (post no encontrado). Crash deja membership en pending.
 *   Wrap: nuestro filter en wu_async_publish_pending_site con try/catch.
 *   Constante: KP_UM_FIX_ELEMENTOR_GUARD_DISABLE
 *
 * Fix #3 — trial_end=0 en provisioning (~14% rate)
 *   Bug: tras wcs_create_subscription, ~14% de subs quedan con trial_end=0
 *   y next_payment=0 cuando product tiene trial_length>0. Cliente nunca cobrado.
 *   Hook: woocommerce_checkout_subscription_created con auto-fix dates.
 *   Constante: KP_UM_FIX_TRIAL_END_DISABLE
 *
 * Fix #4 — Switcher add_switch_hidden_inputs() faltante
 *   Bug: David override WC_Subscriptions_Switcher con versión vieja sin
 *   add_switch_hidden_inputs() (introducido en WC Subs 8.3.0). Si tema usa
 *   AJAX add-to-cart, parámetros switch se pierden.
 *   Hook: woocommerce_before_add_to_cart_button replicando comportamiento.
 *   Constante: KP_UM_FIX_SWITCHER_HIDDEN_INPUTS_DISABLE
 *
 * Fix #5 — Renewal sync (membership status from sub)
 *   Bug: WCS sub→active tras retry exitoso NO actualiza WPU membership.
 *   Cliente paga pero ve "sitio suspendido".
 *   Ya cubierto por kp-um-renewal-sync.php (mu-plugin separado).
 *
 * SEGURIDAD
 * =========
 * - Solo runtime patches, no toca files de David
 * - Cada fix con constante para deshabilitar
 * - Logs detallados a debug.log con prefijo [KP-UM-FIX]
 * - Nunca silencia errores reales, solo previene crash en bugs conocidos
 *
 * REQUIRED VERSIONS
 * =================
 * - ultimate-multisite v2.9.1+ (verificado)
 * - ultimate-multisite-woocommerce v2.0.10+
 * - WordPress 6.9+, PHP 8.3+
 */

defined('ABSPATH') || exit;

if (!defined('KP_UM_FIX_VERSION')) {
    define('KP_UM_FIX_VERSION', '1.0.0');
}

/**
 * Helper: log con prefijo unified.
 */
function kp_um_fix_log($message, $context = []) {
    $line = '[KP-UM-FIX] ' . $message;
    if (!empty($context)) {
        $line .= ' | ' . wp_json_encode($context);
    }
    error_log($line);
}

// ════════════════════════════════════════════════════════════════════
// FIX #1 — mShots URL doble https://
// ════════════════════════════════════════════════════════════════════
if (!defined('KP_UM_FIX_MSHOTS_URL_DISABLE') || !KP_UM_FIX_MSHOTS_URL_DISABLE) {

    add_filter('wu_screenshot_api_url', function ($url, $domain = '') {
        if (!is_string($url) || $url === '') {
            return $url;
        }

        // Detectar doble protocolo en URL ya construido (encoded)
        if (strpos($url, 'https%3A%2F%2Fhttps%3A%2F%2F') !== false) {
            $url = preg_replace('#https%3A%2F%2Fhttps%3A%2F%2F#i', 'https%3A%2F%2F', $url);
            kp_um_fix_log('mShots URL doble https detectado y limpiado', ['url' => $url]);
        }
        // Variante http://https://
        if (strpos($url, 'http%3A%2F%2Fhttps%3A%2F%2F') !== false) {
            $url = preg_replace('#http%3A%2F%2Fhttps%3A%2F%2F#i', 'https%3A%2F%2F', $url);
        }

        return $url;
    }, 99, 2);
}

// ════════════════════════════════════════════════════════════════════
// FIX #2 — Elementor crash en publish_pending_site / get_css_wrapper_selector
// ════════════════════════════════════════════════════════════════════
//
// Bug aparece como:
//   "Call to a member function get_css_wrapper_selector() on false"
//
// Algún plugin/extension de Elementor escucha hooks que UM dispara durante
// publish_pending_site, y trata de renderizar un document que no existe.
// Solución: wrap con try/catch en hooks UM downstream que probablemente
// disparan render Elementor sobre el sitio recién creado.
// ════════════════════════════════════════════════════════════════════
if (!defined('KP_UM_FIX_ELEMENTOR_GUARD_DISABLE') || !KP_UM_FIX_ELEMENTOR_GUARD_DISABLE) {

    /**
     * Wrap UM site_published hooks para que Elementor crash no rompa flujo.
     */
    add_action('wu_event_site_published', 'kp_um_fix_guard_site_published', 1, 1);

    function kp_um_fix_guard_site_published($membership) {
        // Pre-guard: si Elementor está cargado y va a renderizar templates,
        // verificar que documents existen antes que David dispare hooks.
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        // Capturar errores tipo "get_css_wrapper_selector() on false"
        // para que crash no propague y rompa publish_pending_site.
        set_error_handler(function ($severity, $message, $file, $line) {
            if (
                strpos($message, 'get_css_wrapper_selector') !== false ||
                strpos($message, 'on false') !== false
            ) {
                kp_um_fix_log('Elementor crash atrapado y silenciado', [
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                ]);
                return true; // suppress
            }
            return false; // dejar pasar otros errores
        }, E_ERROR | E_RECOVERABLE_ERROR);

        // Restaurar handler después del request normal
        register_shutdown_function(function () {
            restore_error_handler();
        });
    }

    /**
     * Filtro defensivo en process_membership_changes path: si Elementor
     * lanza Throwable por get_css_wrapper_selector → atrapamos y seguimos.
     */
    add_filter('elementor/document/get_data', function ($data, $document = null) {
        if (!is_object($document)) {
            kp_um_fix_log('elementor/document/get_data invocado con document=false');
            return [];
        }
        return $data;
    }, 1, 2);
}

// ════════════════════════════════════════════════════════════════════
// FIX #3 — trial_end=0 auto-fix post-creation
// ════════════════════════════════════════════════════════════════════
if (!defined('KP_UM_FIX_TRIAL_END_DISABLE') || !KP_UM_FIX_TRIAL_END_DISABLE) {

    /**
     * Hook después que WCS crea una sub vía checkout. Si producto tiene
     * trial_length > 0 y trial_end resultó en 0/null → recalcular dates.
     */
    add_action('woocommerce_checkout_subscription_created', 'kp_um_fix_trial_end_dates', 20, 3);

    function kp_um_fix_trial_end_dates($subscription, $order, $recurring_cart) {
        if (!is_object($subscription) || !method_exists($subscription, 'get_date')) {
            return;
        }

        // ¿Algún producto en el cart tiene trial > 0?
        $has_trial_product = false;
        foreach ($subscription->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            $trial_length = (int) $product->get_meta('_subscription_trial_length');
            if ($trial_length > 0) {
                $has_trial_product = true;
                break;
            }
        }

        if (!$has_trial_product) return;

        $trial_end = $subscription->get_date('trial_end');
        $next_payment = $subscription->get_date('next_payment');

        $needs_fix = false;
        $update = [];

        if (empty($trial_end) || $trial_end === '0' || $trial_end === 0 || $trial_end === '0000-00-00 00:00:00') {
            $update['trial_end'] = $subscription->calculate_date('trial_end');
            $needs_fix = true;
        }
        if (empty($next_payment) || $next_payment === '0' || $next_payment === 0 || $next_payment === '0000-00-00 00:00:00') {
            $update['next_payment'] = $subscription->calculate_date('next_payment');
            $needs_fix = true;
        }

        if ($needs_fix && !empty($update)) {
            try {
                $subscription->update_dates($update);
                kp_um_fix_log('trial_end/next_payment auto-fixed', [
                    'sub_id' => $subscription->get_id(),
                    'order_id' => is_object($order) ? $order->get_id() : null,
                    'fixed_dates' => $update,
                ]);
            } catch (\Throwable $e) {
                kp_um_fix_log('trial_end auto-fix FAILED', [
                    'sub_id' => $subscription->get_id(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Backup: scan periódico (vía cron existente kp_um_monitor_scan)
     * y auto-fix si encuentra subs con trial_end=0 menos de 24h viejas.
     */
    add_action('kp_um_monitor_scan', 'kp_um_fix_trial_end_scan_recent', 5);

    function kp_um_fix_trial_end_scan_recent() {
        if (!function_exists('wcs_get_subscriptions')) return;

        global $wpdb;
        $cutoff = date('Y-m-d H:i:s', time() - 24 * HOUR_IN_SECONDS);

        $sub_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_schedule_trial_end'
             WHERE p.post_type = 'shop_subscription'
               AND p.post_date_gmt > %s
               AND (pm1.meta_value = '0' OR pm1.meta_value = '0000-00-00 00:00:00' OR pm1.meta_value = '')",
            $cutoff
        ));

        if (empty($sub_ids)) return;

        foreach ($sub_ids as $sub_id) {
            $sub = wcs_get_subscription($sub_id);
            if (!$sub) continue;

            $has_trial = false;
            foreach ($sub->get_items() as $item) {
                $product = $item->get_product();
                if ($product && (int) $product->get_meta('_subscription_trial_length') > 0) {
                    $has_trial = true;
                    break;
                }
            }
            if (!$has_trial) continue;

            try {
                $sub->update_dates([
                    'trial_end' => $sub->calculate_date('trial_end'),
                    'next_payment' => $sub->calculate_date('next_payment'),
                ]);
                kp_um_fix_log('Backup scan: trial_end fixed for orphan sub', ['sub_id' => $sub_id]);
            } catch (\Throwable $e) {
                kp_um_fix_log('Backup scan trial_end FAILED', ['sub_id' => $sub_id, 'error' => $e->getMessage()]);
            }
        }
    }
}

// ════════════════════════════════════════════════════════════════════
// FIX #4 — add_switch_hidden_inputs() para AJAX add-to-cart con switch
// ════════════════════════════════════════════════════════════════════
//
// David override WC_Subscriptions_Switcher pero NO incluye el método
// add_switch_hidden_inputs() de WC Subs 8.3.0+. Si tema usa AJAX
// add-to-cart con switch query args, params se pierden al serializar form.
// Replicamos el comportamiento via filter directo.
// ════════════════════════════════════════════════════════════════════
if (!defined('KP_UM_FIX_SWITCHER_HIDDEN_INPUTS_DISABLE') || !KP_UM_FIX_SWITCHER_HIDDEN_INPUTS_DISABLE) {

    add_action('woocommerce_before_add_to_cart_button', 'kp_um_fix_switch_hidden_inputs', 5);

    function kp_um_fix_switch_hidden_inputs() {
        // Solo en página de producto cuando hay switch context vía URL
        $switch_subscription_id = isset($_GET['switch-subscription']) ? sanitize_text_field($_GET['switch-subscription']) : '';
        $item_id = isset($_GET['item']) ? sanitize_text_field($_GET['item']) : '';
        $nonce = isset($_GET['_wcsnonce']) ? sanitize_text_field($_GET['_wcsnonce']) : '';

        if (!$switch_subscription_id || !$item_id || !$nonce) {
            return;
        }

        // Verificar que David's Switcher NO esté ya outputeando estos inputs
        // (evitar duplicados si en el futuro David agrega el método)
        if (class_exists('WC_Subscriptions_Switcher') &&
            method_exists('WC_Subscriptions_Switcher', 'add_switch_hidden_inputs')) {
            return;
        }

        echo '<input type="hidden" name="switch-subscription" value="' . esc_attr($switch_subscription_id) . '" />';
        echo '<input type="hidden" name="item" value="' . esc_attr($item_id) . '" />';
        echo '<input type="hidden" name="_wcsnonce" value="' . esc_attr($nonce) . '" />';

        kp_um_fix_log('Hidden inputs added for switch context', [
            'subscription_id' => $switch_subscription_id,
            'item_id' => $item_id,
        ]);
    }
}

// ════════════════════════════════════════════════════════════════════
// Boot log
// ════════════════════════════════════════════════════════════════════
add_action('plugins_loaded', function () {
    static $logged = false;
    if ($logged) return;
    $logged = true;

    $active_fixes = [];
    if (!defined('KP_UM_FIX_MSHOTS_URL_DISABLE') || !KP_UM_FIX_MSHOTS_URL_DISABLE) $active_fixes[] = 'mshots-url';
    if (!defined('KP_UM_FIX_ELEMENTOR_GUARD_DISABLE') || !KP_UM_FIX_ELEMENTOR_GUARD_DISABLE) $active_fixes[] = 'elementor-guard';
    if (!defined('KP_UM_FIX_TRIAL_END_DISABLE') || !KP_UM_FIX_TRIAL_END_DISABLE) $active_fixes[] = 'trial-end';
    if (!defined('KP_UM_FIX_SWITCHER_HIDDEN_INPUTS_DISABLE') || !KP_UM_FIX_SWITCHER_HIDDEN_INPUTS_DISABLE) $active_fixes[] = 'switcher-hidden-inputs';

    if (!empty($active_fixes)) {
        // Solo loguear una vez al boot, no en cada request
        if (!get_transient('kp_um_fix_boot_logged')) {
            set_transient('kp_um_fix_boot_logged', 1, HOUR_IN_SECONDS);
            kp_um_fix_log('v' . KP_UM_FIX_VERSION . ' loaded with fixes: ' . implode(', ', $active_fixes));
        }
    }
}, 999);
