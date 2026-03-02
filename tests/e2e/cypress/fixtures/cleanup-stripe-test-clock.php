<?php
/**
 * Delete a Stripe Test Clock to clean up after renewal tests.
 *
 * Usage: wp eval-file cleanup-stripe-test-clock.php <sk_key> <clock_id>
 */

$sk_key   = isset($args[0]) ? $args[0] : '';
$clock_id = isset($args[1]) ? $args[1] : '';

if (empty($sk_key) || empty($clock_id)) {
	echo wp_json_encode(['error' => 'Missing arguments. Expected: sk_key, clock_id']);
	return;
}

try {
	$stripe = new \Stripe\StripeClient($sk_key);
	$stripe->testHelpers->testClocks->delete($clock_id);

	echo wp_json_encode([
		'success'  => true,
		'clock_id' => $clock_id,
		'deleted'  => true,
	]);
} catch (\Exception $e) {
	echo wp_json_encode([
		'success'  => false,
		'error'    => $e->getMessage(),
		'clock_id' => $clock_id,
	]);
}
