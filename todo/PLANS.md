# Plans

## t523: PayPal PPCP Integration Review Compliance

**Status:** Planning
**Estimate:** ~8h (code) + manual deliverables
**Priority:** High — blocks PayPal partnership approval
**Tags:** #paypal #gateway #compliance

### Context from Discussion

PayPal reviewed our integration and provided specific feedback requiring changes before
approval. The feedback covers 6 areas: UI requirements, seller onboarding, account
validation, order/capture process, BN code, and debug ID submission.

**Architecture context:**
- Two PayPal gateways exist: Legacy NVP (`PayPal_Gateway`) and REST (`PayPal_REST_Gateway`)
- Only the REST gateway matters for the review — legacy is hidden from new installs
- OAuth onboarding is delegated to a proxy at `ultimatemultisite.com/wp-json/paypal-connect/v1`
- The proxy holds partner credentials and handles `/v2/customer/partner-referrals` calls
- Merchant status (`payments_receivable`, `email_confirmed`) is stored but not enforced

**Key decisions:**
- `NO_SHIPPING` is correct — we sell digital WaaS subscriptions, not physical goods
- BN code `ULTIMATE_SP_PPCP` is already implemented on all REST API calls
- The partner account architecture is correct (proxy = partner, merchants connect own accounts)
- Platform fees are handled via `PayPal-Auth-Assertion` header + `payment_instruction`

**What passed review:**
- BN code implementation (ULTIMATE_SP_PPCP on all headers)
- NO_SHIPPING preference on orders and subscriptions
- Order creation via /v2/checkout/orders with CAPTURE intent
- Order capture via /v2/checkout/orders/{id}/capture
- Line items include name, description, unit_amount, quantity, category
- Webhook handling for subscription and payment events

**What needs fixing (code — this repo):**
1. Disconnect dialog uses generic text, needs PayPal's required disclaimer
2. No UI feedback when payments_receivable=false or email_confirmed=false after onboarding
3. Gateway not blocked at checkout when merchant account status is invalid
4. Missing payee.merchant_id in purchase_units for OAuth mode orders
5. PayPal-Debug-Id response header not captured in logs

**What needs verification (proxy — ultimatemultisite.com):**
- Partner-referrals call must include ACCESS_MERCHANT_INFORMATION feature
- Products field must use PPCP (not EXPRESS_CHECKOUT)
- BN code must be sent on partner-referrals header
- /oauth/verify must call /v1/customer/partners/{partner_id}/merchant-integrations/{merchant_id}

**Open questions:**
- Is the proxy partner account configured as platform-only (no direct payments)?
- Does the proxy currently use EXPRESS_CHECKOUT or PPCP as the product?

### Execution Plan

#### Phase 1: Code Changes (this repo) ~6h

**t523a: Disconnect disclaimer** (~30min)
- File: `inc/gateways/class-paypal-rest-gateway.php` line 1892
- Change confirm() text to: "Disconnecting your PayPal account will prevent you from
  offering PayPal services and products on your website. Do you wish to continue?"

**t523b: Onboarding failure UI** (~2h)
- File: `inc/gateways/class-paypal-rest-gateway.php` (render_oauth_connection method)
- After successful OAuth, check stored `payments_receivable` and `email_confirmed`
- Display PayPal's required error messages when either is false:
  - payments_receivable=false: "Attention: You currently cannot receive payments due to
    restriction on your PayPal account. Please reach out to PayPal Customer Support or
    connect to https://www.paypal.com for more information."
  - email_confirmed=false: "Attention: Please confirm your email address on
    https://www.paypal.com/businessprofile/settings in order to receive payments! You
    currently cannot receive payments."

**t523c: Block checkout for invalid merchant status** (~1h)
- File: `inc/gateways/class-paypal-rest-gateway.php`
- Hook into `wu_get_active_gateways` (existing pattern for currency check)
- Remove paypal-rest from active gateways when payments_receivable or email_confirmed is false

**t523d: Add payee.merchant_id to orders** (~1h)
- File: `inc/gateways/class-paypal-rest-gateway.php` (create_order method)
- Add `payee.merchant_id` to `purchase_units[0]` when `$this->merchant_id` is set

**t523e: Log PayPal-Debug-Id headers** (~1h)
- File: `inc/gateways/class-paypal-rest-gateway.php` (api_request method)
- After each API response, extract and log the `PayPal-Debug-Id` response header

#### Phase 2: Proxy Verification (manual — ultimatemultisite.com)

- Verify partner-referrals includes ACCESS_MERCHANT_INFORMATION
- Verify products field uses PPCP
- Verify BN code on partner-referrals header
- Verify /oauth/verify calls merchant-integrations API correctly
- Confirm partner account is platform-only

#### Phase 3: Review Deliverables (manual)

- Capture debug IDs from sandbox test calls (all 4 endpoints)
- Record buyer checkout flow video/screenshots
- Screenshot onboarding failure scenarios (both error messages)
- Screenshot disconnect confirmation dialog
- Submit all to PayPal

### Files Affected

- `inc/gateways/class-paypal-rest-gateway.php` (main changes)
- `inc/gateways/class-paypal-oauth-handler.php` (may need status check helper)
- Proxy server code (separate repo/server)
