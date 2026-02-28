<?php
/**
 * Enable the PayPal REST gateway with sandbox API keys for e2e testing.
 * Usage: wp eval-file setup-paypal-gateway.php <client_id> <client_secret>
 */

$client_id     = isset($args[0]) ? $args[0] : '';
$client_secret = isset($args[1]) ? $args[1] : '';

if (empty($client_id) || empty($client_secret)) {
	echo wp_json_encode(['error' => 'Missing PayPal sandbox keys. Pass client_id and client_secret as arguments.']);
	return;
}

// Enable sandbox mode
wu_save_setting('paypal_rest_sandbox_mode', 1);

// Set sandbox keys
wu_save_setting('paypal_rest_sandbox_client_id', $client_id);
wu_save_setting('paypal_rest_sandbox_client_secret', $client_secret);

// Show manual keys so settings reflect the credentials
wu_save_setting('paypal_rest_show_manual_keys', 1);

// Add paypal-rest to active gateways while keeping existing ones
$active_gateways = (array) wu_get_setting('active_gateways', []);

if (!in_array('paypal-rest', $active_gateways, true)) {
	$active_gateways[] = 'paypal-rest';
}

wu_save_setting('active_gateways', $active_gateways);

echo wp_json_encode(
	[
		'success'          => true,
		'active_gateways'  => wu_get_setting('active_gateways', []),
		'sandbox_mode'     => wu_get_setting('paypal_rest_sandbox_mode', false),
		'client_id_set'    => !empty(wu_get_setting('paypal_rest_sandbox_client_id')),
		'client_secret_set' => !empty(wu_get_setting('paypal_rest_sandbox_client_secret')),
	]
);
