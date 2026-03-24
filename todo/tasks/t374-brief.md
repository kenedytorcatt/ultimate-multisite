# Task Brief: t374 - Add support for creating new domains in Cloudflare

## Session Origin
- GitHub issue #374 created by superdav42 on Mar 24, 2026
- Issue URL: https://github.com/Ultimate-Multisite/ultimate-multisite/issues/374

## What
Add support for creating new domains in Cloudflare via API. Currently, the Cloudflare integration only supports adding subdomains and their SSL certificates. The enhancement should enable automatic creation of Custom Hostnames in Cloudflare when a new domain is added to the multisite backend.

## Why
Cloudflare SaaS is the only practical way to deploy hundreds of SSL certificates for subsites in a multisite environment. The current integration is insufficient for large-scale deployments where multiple custom domains need to be provisioned automatically.

## How
1. Research Cloudflare API endpoints for creating Custom Hostnames
2. Identify the appropriate plugin(s) that handle domain creation in the multisite backend
3. Implement API integration to create Custom Hostnames when new domains are added
4. Handle error cases and provide appropriate feedback
5. Ensure the integration works with Cloudflare SaaS API authentication

## Acceptance Criteria
- [ ] When a new domain is added to the multisite backend, the system automatically creates a Custom Hostname in Cloudflare via API
- [ ] The Custom Hostname is created with the correct SSL settings (flexible/strict/origin)
- [ ] Error handling is in place for API failures (invalid credentials, rate limits, domain already exists, etc.)
- [ ] The integration is tested with a real Cloudflare account (using test credentials or sandbox)
- [ ] Documentation is updated to explain the new Cloudflare domain creation workflow

## Context
- Current Cloudflare integration: Only supports adding subdomains and SSL certificates
- Target Cloudflare API: Custom Hostnames endpoint
- Plugin context: Ultimate Multisite core or relevant addon plugin (likely `ultimate-multisite-domain-seller` or similar)
- Authentication: Cloudflare API token with appropriate permissions
- Environment: WordPress multisite at tgc.church (dev/staging)
- Related: Issue #373 (same topic, created by same user)