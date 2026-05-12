# KursoPro mu-plugin inventory — reference for UM maintainers

This branch is **not a PR**. It exists purely to make our currently-active mu-plugins readable from a URL so they can be linked from a GitHub issue.

We run a multisite SaaS on top of Ultimate Multisite (kursopro.com, ~300 subsites, ~50 mapped custom domains). Over the past six months we accumulated a set of mu-plugins that patch behaviour we couldn't fix upstream without losing time. Some of them are real bugs in UM core. Some are backports of code we found in your `main` branch but that hadn't been released yet. Some are workarounds for races that surface only at production scale.

If you'd like a PR for any of these, tell us which one and we'll prepare it cleanly against `main`. We're not opening 8 PRs at once because we don't want to flood your queue.

## Files in this branch

| File | Purpose | Status |
|---|---|---|
| `kp-um-template-switch-fix.php` | 10 bugs in `wu_switch_template` AJAX (timeout, blogname overwrite, Elementor Kit/CSS not copied, breakpoints null fatal, etc) | Active in prod, v3.5.0 |
| `kp-um-cookieless-sso.php` | Backport of cookie-less cross-domain SSO (emitter on main, receiver on mapped domain) for Chrome 3rd-party-cookie blocking | Active in prod, v1.0.1 |
| `kp-um-cookie-less-sso-token.php` | Earlier iteration of the cookieless SSO backport. Same intent. | Predecessor of `kp-um-cookieless-sso.php` |
| `kp-um-sso-loop-breaker.php` | Counter cookie on the main domain that breaks the cross-domain SSO redirect loop (mapped domain → main → mapped → …) | Active in prod, v3.0.0 |
| `kp-sso-mapped-fix.php` | Admin bar SSO on mapped domains (PR #366 regression) | Active in prod, v1.1.0 |
| `kp-fix-um-blogname.php` | Force-applies the customer-provided `$args->title` during site duplication; otherwise the template blogname leaks through | Active in prod, v1.0.0 |
| `kp-fix-um-domain-mapping.php` | Adds missing `option_home` filter, flushes rewrite on creation, enforces HTTPS | Active in prod, v1.0.0 |
| `kp-um-bugs-fixes.php` | Bag of small defensive fixes against UM 2.9.1 + UM-WC 2.0.10 | Active in prod, v1.0.0 |

The accompanying issue describes each bug with reproduction steps and a clear pointer to the file in this branch.

## Why a reference branch instead of issue attachments

Files are easier to read with syntax highlighting in a branch view, and the URLs are stable. We will keep this branch around as long as the issue is open.
