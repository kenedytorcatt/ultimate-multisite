# t450: Positioning & Growth Plan — Ultimate Multisite

**Issue:** #450 | **Date:** 2026-03-25 | **Author:** AI DevOps

---

## 1. Current State Assessment

### What the plugin actually is
A WordPress Multisite plugin that turns a network into a self-serve Website-as-a-Service (WaaS) platform: subscription plans, site provisioning from templates, custom domain mapping, Stripe/PayPal billing, customer dashboard, white-labelling, and hosting integrations (Cloudflare, GridPane, Cloudways, WP Engine, cPanel, RunCloud, etc.).

### Current positioning (verbatim from readme.txt)
> "Ultimate Multisite turns your WordPress network into a WaaS platform with subscriptions, site provisioning, and domain mapping."

Target audience as stated: agencies, creators, hosts/MSPs, franchises, universities, internal teams.

### Download signal
| Release | Date | Downloads |
|---------|------|-----------|
| v2.3.4 | Jan 2025 | 977 |
| v2.4.3 | Aug 2025 | 273 |
| v2.4.1 | Jul 2025 | 255 |
| v2.4.12 | Feb 2026 | 136 |

The v2.3.4 spike was the WP Ultimo community discovering the fork. Subsequent releases show no growth trend — the existing audience is downloading updates but new users are not arriving in volume.

### GitHub signal
- 186 stars, 73 forks — healthy for a niche plugin, but not growing fast
- Topics: `multisite-network`, `wordpress`, `wordpress-plugin` — generic, not searchable by use case
- One maintainer (superdav42) doing 582 of ~600 total commits — bus factor risk, also limits community perception

---

## 2. Positioning Gaps

### Gap 1: "WaaS" is jargon — most potential users don't search for it
The current copy leads with "WaaS platform" and "Website as a Service." These are terms used by people who already know what they want to build. The much larger audience — WordPress agencies, freelancers, and developers who want to sell recurring website services — does not use this vocabulary. They search for "sell WordPress sites", "WordPress subscription sites", "WordPress client portal", "multisite billing plugin."

**Evidence:** The readme.txt FAQ and feature list are technically accurate but written for someone who already understands multisite architecture. There is no copy that says "if you build websites for clients and want to charge them monthly, this is how you do it."

### Gap 2: The WP Ultimo brand is stronger than "Ultimate Multisite" — and is being underused
WP Ultimo had a paid customer base and brand recognition. The readme.txt mentions it once in the description and once in the FAQ. The plugin's GitHub org is `Ultimate-Multisite` but the repo was forked from `superdav42/wp-multisite-waas`. Searches for "WP Ultimo free" or "WP Ultimo alternative" are high-intent and currently not well-captured.

**Evidence:** readme.txt has an "Also Known As" section but it's buried below the fold. The GitHub topics don't include `wp-ultimo`.

### Gap 3: WordPress.org is the primary discovery channel — and the listing is not optimised for it
The plugin short description (the 150-character summary shown in search results) is: "Ultimate Multisite turns your WordPress network into a WaaS platform with subscriptions, site provisioning, and domain mapping." This is accurate but not compelling to a user scanning search results. It doesn't answer "what problem does this solve for me."

The plugin has no screenshots beyond a placeholder list, no banner image, and no icon — all of which affect click-through rate on WordPress.org.

### Gap 4: The free-and-open-source angle is not being used as a differentiator
The main paid competitor (WP Ultimo original) was $199+/year. This plugin is free. That is a significant differentiator that is mentioned nowhere prominently. The readme.txt says "community-maintained" but doesn't say "free" or "no license fee."

### Gap 5: No social proof or case studies
There are no testimonials, no "built with Ultimate Multisite" examples, no case studies. A potential user evaluating the plugin has no evidence that anyone has successfully used it to build a real business.

### Gap 6: The Stripe 3% fee is a hidden cost that damages trust
The readme.txt discloses a 3% per-transaction fee applied via Stripe Connect, waived if you purchase any addon. This is a legitimate business model but it is disclosed in the "External Services" section (below the fold, in fine print). A user who discovers this after setup will feel misled. A user who discovers it before setup may choose a competitor. Either way, it is not being handled as a feature — it is being hidden as a footnote.

---

## 3. Target Audience — Revised Segmentation

The current positioning targets a sophisticated operator who already knows they want to build a WaaS business. That is a real segment but a small one. Three larger, adjacent segments are being missed:

**Segment A: WordPress agencies and freelancers who want recurring revenue**
These are people who currently build one-off WordPress sites for clients and want to move to a subscription model. They are not thinking "WaaS" — they are thinking "how do I charge clients monthly for their website." This is the largest addressable segment. They need copy that says: "Stop building one-off sites. Charge clients monthly. Ultimate Multisite handles the billing, the site provisioning, and the client portal."

**Segment B: WP Ultimo users looking for a free alternative**
WP Ultimo's original developer stopped active development. There is an active community of WP Ultimo users who need a maintained, free alternative. This segment is high-intent and already understands the product category. They need to find this plugin when they search "WP Ultimo free" or "WP Ultimo alternative."

**Segment C: Niche site builder creators**
People who want to build a vertical-specific site builder (e.g., "WordPress sites for restaurants", "sites for real estate agents") using WordPress Multisite as the infrastructure. This segment is growing as no-code/low-code tools proliferate. They need copy that says: "Build your own Squarespace for [your niche] on WordPress."

---

## 4. Actionable Plan

Actions are ordered by impact-to-effort ratio. High-impact, low-effort actions first.

---

### Action 1: Rewrite the WordPress.org short description (1 hour, high impact)

**Current:** "Ultimate Multisite turns your WordPress network into a WaaS platform with subscriptions, site provisioning, and domain mapping."

**Proposed:** "Sell recurring WordPress websites to clients. Ultimate Multisite adds subscription billing, site provisioning, and a client portal to your WordPress Multisite network — free and open source."

**Why:** The short description is the first thing a user sees in WordPress.org search results. "Sell recurring WordPress websites to clients" directly addresses Segment A's job-to-be-done. "Free and open source" addresses Gap 4.

**File to change:** `readme.txt` (the one-line description after the plugin header block, line 12).

---

### Action 2: Add `wp-ultimo` and use-case GitHub topics (15 minutes, medium impact)

**Current topics:** `multisite-network`, `wordpress`, `wordpress-plugin`

**Proposed topics:** `multisite-network`, `wordpress`, `wordpress-plugin`, `wp-ultimo`, `waas`, `saas`, `multisite-billing`, `wordpress-multisite`, `site-builder`

**Why:** GitHub topic search is used by developers evaluating plugins. `wp-ultimo` captures Segment B directly. Use-case topics (`site-builder`, `multisite-billing`) improve discoverability.

**How:** Settings → Topics on the GitHub repo page (no code change needed).

---

### Action 3: Rewrite the readme.txt description opening (2 hours, high impact)

**Current opening:** "Ultimate Multisite is a WordPress Multisite plugin designed to help you build, sell, and manage a Website-as-a-Service (WaaS) platform on your own infrastructure."

**Proposed opening:**

```
**Stop building one-off WordPress sites. Start charging clients monthly.**

Ultimate Multisite turns your WordPress Multisite network into a subscription-based website business. Your clients sign up, choose a plan, and get their own WordPress site — provisioned automatically, billed automatically, managed from one dashboard.

Free and open source. No license fee. Formerly known as WP Ultimo.
```

**Why:** Leads with the outcome (recurring revenue), not the technology (WaaS). Addresses Segment A directly. Mentions WP Ultimo in the first paragraph to capture Segment B.

**File to change:** `readme.txt` lines 14–22.

---

### Action 4: Make the Stripe fee a transparent, front-and-centre feature (1 hour, trust impact)

The 3% Stripe fee is currently disclosed in the "External Services" section. It should be disclosed prominently in the description, framed as a fair trade:

Add to the "Why Choose Ultimate Multisite" section:

```
- **Transparent Pricing** – Free to use. A 3% transaction fee applies via Stripe Connect to support ongoing development; this fee is waived when you purchase any add-on from ultimatemultisite.com.
```

And add a FAQ entry:

```
= Is there a transaction fee? =

Yes. When using the built-in Stripe integration, a 3% fee per transaction is applied to support ongoing development. This fee is waived for sites that have purchased any add-on from ultimatemultisite.com. There is no monthly or annual license fee for the plugin itself.
```

**Why:** Transparency builds trust. A user who discovers the fee after setup is a churned user. A user who understands the model upfront is a customer who can make an informed decision — and is more likely to purchase an add-on to waive the fee.

**File to change:** `readme.txt`.

---

### Action 5: Add "WP Ultimo" to the plugin name and search terms (30 minutes, high impact for Segment B)

The readme.txt header currently reads:
```
=== Ultimate Multisite – WordPress Multisite SaaS & WaaS Platform ===
```

Proposed:
```
=== Ultimate Multisite (WP Ultimo) – WordPress Multisite Subscription & Site Builder ===
```

And expand the Tags line:
```
Tags: multisite, domain mapping, wordpress multisite, multisite saas, waas, wp-ultimo, site builder, subscription, recurring billing
```

**Why:** WordPress.org search indexes the plugin name and tags. "WP Ultimo" in the name directly captures Segment B. "Subscription" and "recurring billing" capture Segment A's search vocabulary. WordPress.org allows up to 5 tags — the current 5 are all technical; replacing 2 with user-intent terms improves discoverability.

**Note:** WordPress.org limits plugin names to avoid keyword stuffing. The parenthetical "(WP Ultimo)" is a legitimate "also known as" reference, not keyword stuffing, since this is the actual successor to WP Ultimo.

**File to change:** `readme.txt` line 1 and Tags line.

---

### Action 6: Add screenshots and a plugin banner to the WordPress.org listing (4 hours, medium impact)

The readme.txt lists 4 screenshots but they are placeholders. WordPress.org displays screenshots prominently. A plugin with no real screenshots has a significantly lower conversion rate from search result to install.

**Deliverables needed:**
- `assets/screenshot-1.png` — The network admin dashboard showing customer list and revenue
- `assets/screenshot-2.png` — The checkout/signup flow a customer sees
- `assets/screenshot-3.png` — The plan/pricing configuration screen
- `assets/screenshot-4.png` — The customer's site management dashboard
- `assets/banner-1544x500.png` — WordPress.org plugin banner (required for featured placement)
- `assets/icon-256x256.png` — Plugin icon

**Why:** Screenshots are the primary conversion driver on WordPress.org after the short description. The banner image is required for any featured or promoted placement.

---

### Action 7: Create a "Built with Ultimate Multisite" community thread or page (3 hours, social proof)

Create a GitHub Discussion or a page on ultimatemultisite.com titled "Built with Ultimate Multisite" and invite existing users (via a notice in the plugin admin, or via the GitHub community) to share their platforms.

**Why:** Social proof is the most effective trust signal for a plugin with no paid marketing. Even 3–5 real examples of live platforms built with the plugin dramatically reduce the "will this actually work?" objection for new users.

**Implementation:** Add a "Share your platform" link in the plugin's admin dashboard first-steps widget (already exists as a component). Link to a GitHub Discussion or a form on ultimatemultisite.com.

---

### Action 8: Write one "how to" blog post targeting Segment A's search intent (4 hours, SEO)

**Target keyword:** "how to sell WordPress websites monthly" or "WordPress recurring website subscription"

**Post title:** "How to Charge Clients Monthly for WordPress Websites (Without Custom Code)"

**Content:** A practical walkthrough of setting up Ultimate Multisite to sell recurring WordPress website subscriptions to clients. Includes screenshots, pricing strategy advice, and a link to the plugin.

**Why:** This keyword has commercial intent and low competition. A single well-written post on ultimatemultisite.com targeting this phrase can drive consistent organic traffic from Segment A. The post also serves as a shareable asset for communities (WordPress Facebook groups, Reddit r/wordpress, WP Tavern, etc.).

---

### Action 9: Post in WP Ultimo community spaces (2 hours, Segment B capture)

The WP Ultimo community exists in Facebook groups, the WP Ultimo Slack/Discord (if still active), and WordPress.org support forums. A single post announcing "WP Ultimo is now free and community-maintained as Ultimate Multisite" in these spaces would capture Segment B directly.

**Specific targets:**
- WordPress.org support forum for the original WP Ultimo plugin (post a reply to recent threads)
- Facebook groups: "WP Ultimo Users", "WordPress Multisite", "WordPress Agencies"
- Reddit: r/Wordpress, r/webdev

**Why:** These are high-intent users who already understand the product. The conversion rate from "WP Ultimo user looking for an alternative" to "Ultimate Multisite installer" should be very high.

---

### Action 10: Add a "Multisite Setup Wizard" call-to-action for single-site WordPress users (ongoing, Segment A expansion)

The plugin already includes a "Multisite Setup Wizard" (added in v2.4.11) that guides single-site installs through enabling WordPress Multisite. This is a significant feature that removes the biggest barrier for Segment A users (most WordPress users don't have Multisite enabled).

This feature should be:
1. Mentioned prominently in the readme.txt description (it currently is not)
2. Used as a hook in marketing copy: "Don't have WordPress Multisite? No problem — our setup wizard enables it for you in minutes."

**File to change:** `readme.txt` Key Features section.

---

## 5. Priority Order

| # | Action | Effort | Impact | Do first? |
|---|--------|--------|--------|-----------|
| 1 | Rewrite short description | 1h | High | Yes |
| 5 | Add WP Ultimo to name/tags | 30m | High | Yes |
| 3 | Rewrite description opening | 2h | High | Yes |
| 4 | Transparent Stripe fee | 1h | Trust | Yes |
| 2 | GitHub topics | 15m | Medium | Yes |
| 9 | WP Ultimo community outreach | 2h | High | Yes |
| 10 | Multisite wizard in copy | 30m | Medium | Yes |
| 6 | Screenshots + banner | 4h | Medium | Next sprint |
| 8 | Blog post | 4h | Medium | Next sprint |
| 7 | Built-with page | 3h | Low-medium | Next sprint |

Actions 1, 2, 3, 4, 5, 10 are all changes to `readme.txt` and can be done in a single PR. Actions 9 is outreach (no code). Actions 6, 7, 8 require content creation and are a second sprint.

---

## 6. What This Plan Does Not Address

- **Paid advertising:** Not recommended until organic channels are optimised. The plugin is free; the monetisation model (add-ons, waived Stripe fee) needs to be validated before spending on ads.
- **Pricing model changes:** The 3% Stripe fee + add-on model is reasonable. No change recommended without data on conversion rates.
- **Feature gaps vs competitors:** The plugin's feature set is competitive. The problem is discoverability and messaging, not features.
- **WordPress.org featured placement:** Requires a formal application and editorial review. Not actionable until screenshots and banner are in place (Action 6).
