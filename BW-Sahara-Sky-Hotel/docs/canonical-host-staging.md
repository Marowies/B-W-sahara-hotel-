# Canonical host staging rollout

Branch scope: Duplicate without user-selected canonical only. No deployment or production configuration is performed. Eventual preferred public origin: https://bwsaharaskyhotel.com.

## Implementation

Opt-in global Laravel middleware, after the default trusted-proxy middleware, handles exact www counterpart of APP_URL. It preserves raw path, language and query, uses 302/private/no-store during testing, and compares only host to avoid TLS-termination scheme loops. 301 needs BOTH CANONICAL_HOST_STATUS=301 and CANONICAL_HOST_PERMANENT_CONFIRMED=true after recorded acceptance. Disabled by default.

Origin must be an HTTPS root without www, credentials, path, query or fragment. Nonproduction environments reject the production hotel host. Unrelated/staging hosts never redirect to production. Proxy trust remains unchanged: forwarded headers are honored only through the existing Laravel trusted-proxy policy.

Unsafe methods, cookies, Authorization/X-XSRF headers, signed/token/payment queries and configured booking/payment/account/admin/API path segments pass through unchanged. All cookie-bearing requests intentionally bypass redirection, including consent cookies; this avoids moving an existing host-bound session. Public canonical/hreflang/sitemap/internal URLs still use the configured preferred origin. Forms and new links must use that same origin.

The NEW host redirect is 302. Both existing .htaccess files retain their PRE-EXISTING 301 trailing-slash redirect only for stateless safe public requests, now excluding unsafe methods, cookies, sensitive queries and stateful paths. No hard-coded server/CDN host rule was added. The automated .htaccess check is a static rule contract, not execution on Apache/LiteSpeed.

## Required Hostinger/staging preparation

Exact Hostinger product, document root, TLS termination, proxy IPs and CDN configuration are not established in repository configuration. Assign a primary and exact www alternate to the SAME isolated staging app with valid certificates. No DNS/account changes are executed by this branch. Prefer public_html/public as the Laravel document root where supported; determine which rewrite files execute if hosting uses the repository wrapper.

Use the ACTUAL staging origin, never the production domain:

    APP_ENV=staging
    APP_URL=https://your-staging-host.example
    FORCE_ROOT_URL=https://your-staging-host.example
    FORCE_SCHEMA=https
    CANONICAL_HOST_ENABLED=true
    CANONICAL_HOST_STATUS=302
    CANONICAL_HOST_PERMANENT_CONFIRMED=false

The alternative is exactly www.your-staging-host.example. Leave disabled if this pair cannot be provided. APP_URL aligns console jobs. Existing Botble FORCE_ROOT_URL uses URL::useOrigin to align web routes/model URLs/canonicals/sitemap; this existing code was not replaced. All three origin settings must agree with this environment. Keep staging noindex and disable outgoing indexing/marketing submissions.

If a proxy exists, establish its real allowed IPs and required forwarded headers before configuring Laravel trusted proxies. Never blindly trust all proxies. Review any existing hosting/CDN opposing host or HTTPS redirect on STAGING only. Keep one host-redirect owner. Refresh configuration/application/sitemap caches only after verifying the staging environment and origin. Review absolute CMS menu/content URLs; do not mass-replace external/CDN/media hosts.

## Staging acceptance

- Exact locked Composer install, then php tests/Seo/run.php and php tests/CanonicalHost/run.php.
- Every published EN/AR/ZH room and listing: 200, one clean same-language canonical on primary, reciprocal alternate targets that actually exist and display intended content.
- Ordinary room image/title/category links: no empty/default parameters; real search retains dates/guests/room quantity.
- Both hosts: GET/HEAD, languages, escaped slugs, repeated/query keys, trailing slash and HTTP/TLS termination. Finite correct chain, no loop, no /public leak or wrong-language fallback. Existing safe slash rule may add a hop.
- Cookie sessions, authenticated guest journeys, real signed test email links, unsafe methods and mock callbacks/webhooks: no host redirect or body/method/session/CSRF/signature loss. Exercise unsafe trailing slash requests on the ACTUAL hosting rewrite engine.
- Warm caches from both hosts; inspect ALL sitemap loc values, CMS menu/form/alternate/canonical URLs and console/queued URL samples for primary-origin agreement. Current public sitemap XML was not verified in the earlier browser review.
- Record all results. Only after acceptance and separate release approval may permanent status plus confirmation be proposed. No production environment values are changed here.

Staging rollback: disable CANONICAL_HOST_ENABLED and refresh staging config. Revert the reviewed release artifact separately if the .htaccess changes must be reverted too.

## Local evidence and remaining limits

50 redirect/configuration cases pass using real Laravel requests, URL generator and signed URL verification, isolated middleware execution and static rewrite checks. Full SEO source regressions are run separately. Local reused PHP dependencies differ from the exact committed lock; these tests do not certify a fully installed CMS or the actual host.

Remaining staging gates: Hostinger/proxy/TLS/rewrite behavior, hard-coded CMS links, translations and live alternate existence, sitemap caches, guest sessions and signed links on the actual installed release. Existing signed URLs remain on their old host deliberately. Payment merchant acceptance is outside this SEO issue.
