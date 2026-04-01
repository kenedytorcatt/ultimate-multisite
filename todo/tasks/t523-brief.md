# t523 Brief: PayPal PPCP Integration Review Compliance

## Session Origin
PayPal provided feedback on our PPCP integration during partnership review. Interactive session with superdav42 analyzing the feedback against current codebase.

## What
Implement 5 code changes to satisfy PayPal's integration review requirements:
1. Update disconnect confirmation dialog to use PayPal's required disclaimer wording
2. Add onboarding failure UI showing error messages when `payments_receivable` or `email_confirmed` is false
3. Block gateway at checkout when merchant account status is invalid
4. Add `payee.merchant_id` to `purchase_units` in `create_order()` for OAuth mode
5. Log `PayPal-Debug-Id` response header from all API calls

## Why
PayPal partnership approval is blocked until these changes are made. The review covers 6 areas; items already passing (BN code, NO_SHIPPING, order/capture flow, line items) don't need changes. The 5 items above are the gaps identified in the code analysis.

## How
All changes are in `inc/gateways/class-paypal-rest-gateway.php` with possible helper additions in `inc/gateways/class-paypal-oauth-handler.php`.

1. **Disconnect disclaimer** (line ~1892): Change `confirm()` text to PayPal's exact required wording
2. **Onboarding failure UI** (`render_oauth_connection()` method): After connected state, check stored `payments_receivable` and `email_confirmed` values; render PayPal's exact error messages as warning banners when either is false
3. **Checkout blocking**: Hook into gateway availability (similar to existing currency check pattern) to remove `paypal-rest` when merchant status is invalid
4. **Payee field** (`create_order()` method, line ~1014): Add `'payee' => ['merchant_id' => $this->merchant_id]` to `purchase_units[0]` when merchant_id is set
5. **Debug ID logging** (`api_request()` method): Extract `PayPal-Debug-Id` from `wp_remote_retrieve_header()` and log it

## Acceptance Criteria
- [ ] Disconnect dialog shows: "Disconnecting your PayPal account will prevent you from offering PayPal services and products on your website. Do you wish to continue?"
- [ ] Settings page shows error banner with PayPal's exact text when payments_receivable=false
- [ ] Settings page shows error banner with PayPal's exact text when email_confirmed=false
- [ ] PayPal gateway hidden from checkout when merchant status is invalid
- [ ] Orders include `payee.merchant_id` in purchase_units when merchant_id is set
- [ ] `PayPal-Debug-Id` logged from every API response
- [ ] All existing PayPal tests pass (`vendor/bin/phpunit --filter PayPal`)

## Context
- Full gap analysis performed in conversation — see `todo/PLANS.md` for detailed findings
- Proxy server (ultimatemultisite.com) also needs verification (separate from this task): ACCESS_MERCHANT_INFORMATION feature, PPCP product, BN code on partner-referrals
- PayPal's exact required error message texts are documented in PLANS.md
- After code ships, manual deliverables needed: debug IDs, checkout flow video, failure screenshots
- GitHub issue: GH#725
