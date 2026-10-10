# Website tracking and test environment

The environment-owned integration replaces the existing GoogleTagManagerEnhanced renderer; it does not add a second tag to the theme. Tracking defaults to off. No Google identifiers or credentials are configured in the source. Identifiers in tests are synthetic and cannot contact Google.

## Configure later

- `TRACKING_MODE=off`: no managed loader or event markers.
- `TRACKING_MODE=ga4`: supply the real `GA4_MEASUREMENT_ID` in the deployment environment; direct Google tag only.
- `TRACKING_MODE=gtm`: supply the real `GTM_CONTAINER_ID`; GA4 is configured **inside that container**, never as a second direct loader. GA4_MEASUREMENT_ID is ignored by the site in this mode.
- `TRACKING_CONSENT_COOKIE`: must match the cookie-consent plugin's configured cookie name.

Before enabling, inventory CMS website tracking settings, custom header/body/footer HTML and JS, plugins, theme templates, CDN/proxy injection and the published GTM container. Existing dedicated CMS tracking IDs/code disable the new loader until an operator reconciles them. General CMS snippets containing recognizable Google tracking code also block it. This is not proof that arbitrary external/injected scripts are absent. The source contains other application copies (`bww`, `public_html/old`); confirm the actual deployed document root is this `public_html` application.

The legacy CMS tracking IDs, custom tracking code and debug toggle no longer emit Google tags through this renderer. They are not deleted from the CMS database. A deployment without environment configuration will therefore disable legacy tracking; review the migration before any deployment. The analytics plugin's dashboard/reporting API is separate and remains unchanged.

## Consent and data contract

This uses a basic consent approach: no Google script request until the persisted JSON preferences explicitly contain `analytics: true`. Missing, invalid, rejected and old boolean consent cookies do not grant permission. Advertising storage, ad user data and ad personalization remain denied. No GTM noscript iframe bypasses the gate. The existing cookie dialog publishes `hotel:consent` on save/reject, retains rejection and provides a preferences button for withdrawal. Withdrawal updates consent and reloads into the rejected state. Test cookie-domain/path/lifetime behavior on staging, approve retention policy, and configure the cookie plugin as enabled with analytics preferences. Review whether previously set analytics cookies must also be expired across the site's actual cookie domains.

The only automatically instrumented business events are:

| Event | Trusted page source | Data | Deduplication |
|---|---|---|---|
| `view_item` | Rendered room detail | stable `room-{id}`, category | once per room per document |
| `begin_checkout` | Server accepts the booking-session token and renders the internal checkout | stable room ID, category | once per room per document; opaque SHA-256 attempt key in sessionStorage prevents refresh duplication in the same consenting tab |
| `purchase` | **Not implemented or emitted** | reserved for verified completion | requires the server-side design below |

No guest name, email, phone, booking token, dates, form fields or client-supplied amounts are sent. A checkout attempt hash is used only for local deduplication after consent and is not included in the analytics event. Storage restrictions fall back to document-level deduplication. Client delivery is best effort and is not an exactly-once accounting system. No monetary value is attached to browsing/checkout until a verified price/currency contract is approved.

Direct GA4 explicitly sends one page_view, disables automatic page_view, strips query strings, removes the referrer and page title, and maps booking/checkout token paths to `/booking`. Audit the GA4 stream's enhanced measurement and automatic form/URL collection before enablement.

### Required GTM container contract

Configure one Google tag for GA4 with automatic page_view disabled and with `page_location`, `page_title`, `page_referrer` overridden by data-layer variables `hotel_page_location`, `hotel_page_title`, `hotel_page_referrer`. Use only `hotel_page_view` for page_view and `view_item`/`begin_checkout` custom-event triggers for the two funnel events, reading ecommerce items from the data layer. Disable overlapping all-pages/history/form/click/ecommerce triggers and additional GA loaders. Enforce analytics consent on every tag; do not add marketing/custom-HTML tags which bypass it. Configure all ads-related consent as denied unless a later separately approved design changes that. Verify Preview and DebugView in a dedicated test property/container. Container contents cannot be validated from this repository.

## Real completion design: prerequisite to purchase

The current checkoutSuccess controller finds a booking by URL transaction ID and renders information; it does not prove completion or authorize ownership. Existing booking/payment regression failures also include spoofed data, replay and amount mismatch. Therefore a thank-you URL, checkout button, client redirect, created pending booking or outbound booking-engine click must never emit purchase.

After those payment/authorization defects are addressed, define what the hotel calls a confirmed booking (verified payment completion, or server-approved pay-at-hotel confirmation). In the same server database transaction, persist a consent snapshot and an outbox record keyed uniquely by `(verified booking ID, purchase)` when the server verifies provider signature/status, amount/currency, ownership and state transition. Use a stable non-PII transaction_id, settled value/currency and verified room items from stored server data. Handle webhook replay, retries, refunds, cancellation, multiple tabs and browser reloads against this unique record. If choosing browser delivery, expose the payload only to an authorized session and acknowledge it server-side; if choosing Measurement Protocol, keep its API secret in server secret storage and never in HTML. Consent and a lawful client/session association must be retained and withdrawal handled. Do not implement both delivery channels for the same event. Even an outbox plus GA transaction_id requires delivery reconciliation; it is not an accounting guarantee.

For an external booking engine, require sandbox access, cross-domain/linker support, an authorized confirmation API/webhook and a provider-backed reservation ID. An outbound click remains a separate intent event. No fabricated completion is inferred from returning to the hotel site. Event naming and purchase parameters follow [Google's recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events); consent setup follows [Google's consent guide](https://developers.google.com/tag-platform/security/guides/consent).

## CI and local diagnostics

Run `composer install --no-scripts --no-plugins` from the lock, `php scripts/check-php-syntax.php`, `php tests/Seo/run.php`, `node --test tests/Tracking/managed.test.cjs`, and `php vendor/bin/phpunit`. CI runs PHP 8.2/8.3 with no production secrets or services. PHPUnit creates fresh cache/storage paths, supplies an ephemeral key, bypasses source .env entirely, forces SQLite memory/array mail/session/cache, and blocks Laravel HTTP stray requests. CI additionally disables PHP outbound HTTP/socket functions. No migrations are run against a persistent database.

The default homepage ExampleTest incorrectly assumed a preinstalled CMS and got the same 302 installer redirect on the main baseline. It now checks the exact installer redirect and the Laravel health endpoint; full installed-site behavior remains covered by separate synthetic runtime/staging checks. The missing-slug PHPUnit test exercises the real listener and Slug query with in-memory schema, while isolating locale configuration and relation fixtures.

The Windows execution sandbox reports readable/writable checks as false and rejects atomic renames. Local diagnostic runs used temporary, ignored vendor-only workarounds for those filesystem checks; no workaround is committed. A diagnostic pass is not an unmodified PHPUnit/Ubuntu CI pass. Dependencies reused locally also differ from composer.lock in 21 installed packages; CI installs the exact lock. Composer validation and a no-plugin install dry-run succeeded; no dependency upgrade or lock change is included. Existing abandoned PayPal libraries and dependency/version warnings need a separate review.
