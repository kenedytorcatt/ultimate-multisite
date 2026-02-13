<?php
/**
 * Advance a Stripe Test Clock to a target timestamp and poll until ready.
 *
 * Usage: wp eval-file advance-stripe-test-clock.php <sk_key> <clock_id> <target_timestamp>
 */

$sk_key           = isset($args[0]) ? $args[0] : '';
$clock_id         = isset($args[1]) ? $args[1] : '';
$target_timestamp = isset($args[2]) ? (int) $args[2] : 0;

if (empty($sk_key) || empty($clock_id) || empty($target_timestamp)) {
	echo wp_json_encode(['error' => 'Missing arguments. Expected: sk_key, clock_id, target_timestamp']);
	return;
}

try {
	$stripe = new \Stripe\StripeClient($sk_key);

	// Advance the test clock
	$stripe->testHelpers->testClocks->advance($clock_id, [
		'frozen_time' => $target_timestamp,
	]);

	// Poll until clock status is "ready" (max 60s)
	$max_attempts = 30;
	$status       = 'advancing';

	for ($i = 0; $i < $max_attempts; $i++) {
		sleep(2);

		$clock  = $stripe->testHelpers->testClocks->retrieve($clock_id);
		$status = $clock->status;

		if ($status === 'ready') {
			break;
		}
	}

	echo wp_json_encode([
		'success'     => $status === 'ready',
		'status'      => $status,
		'frozen_time' => $clock->frozen_time ?? null,
		'clock_id'    => $clock_id,
	]);
} catch (\Exception $e) {
	echo wp_json_encode([
		'error' => $e->getMessage(),
		'code'  => $e->getCode(),
	]);
}
