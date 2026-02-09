<?php
/**
 * Verify Stripe renewal test results: payments, membership state.
 *
 * Usage: wp eval-file verify-stripe-renewal-results.php <membership_id>
 */

$membership_id = isset($args[0]) ? (int) $args[0] : 0;

if (empty($membership_id)) {
	echo wp_json_encode(['error' => 'Missing membership_id argument.']);
	return;
}

$membership = wu_get_membership($membership_id);

if (! $membership) {
	echo wp_json_encode(['error' => 'Membership not found: ' . $membership_id]);
	return;
}

// Get all payments for this membership, ordered by ID ascending
$payments = WP_Ultimo\Models\Payment::query([
	'membership_id' => $membership_id,
	'orderby'       => 'id',
	'order'         => 'ASC',
	'number'        => 10,
]);

$payment_details = [];

if ($payments) {
	foreach ($payments as $p) {
		$payment_details[] = [
			'id'                 => $p->get_id(),
			'status'             => $p->get_status(),
			'total'              => (float) $p->get_total(),
			'gateway'            => $p->get_gateway(),
			'gateway_payment_id' => $p->get_gateway_payment_id(),
		];
	}
}

echo wp_json_encode([
	'membership_id'         => $membership->get_id(),
	'membership_status'     => $membership->get_status(),
	'membership_expiration' => $membership->get_date_expiration(),
	'times_billed'          => $membership->get_times_billed(),
	'gateway'               => $membership->get_gateway(),
	'recurring'             => $membership->is_recurring(),
	'auto_renew'            => $membership->should_auto_renew(),
	'payment_count'         => count($payment_details),
	'payments'              => $payment_details,
]);
