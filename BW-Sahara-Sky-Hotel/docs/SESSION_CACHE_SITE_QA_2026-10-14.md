# Session, initial render and site QA — 14 October 2026

Branch `fix/session-cache-site-qa` from `6f4bcfd3`. Local fixture testing only: no Staging or Production access, no real customers, bookings, payments or email.

## Fixed

### 1. "My account" led back to the sign-in page

- **Reproduce:** sign in with an email code, wait until the Laravel session expires (120 minutes) or delete it, reload a public page, choose My account. Result: `/login/` saying "You're signed in", and no dashboard.
- **Cause (confirmed by a failing regression test before the change):** `HotelCustomerAuthController::refresh` rotated the 30-day refresh token and returned an access token, but never restored the Laravel `customer` guard that the account pages use. Only OTP verification did that. A rejected refresh token also left an old page session open.
- **Fix:** after a successful rotation, the customer is resolved only through `HotelTokenService::authenticate()` on the new access token, never from request data, and logged into the `customer` guard. The session id is regenerated only when the identity changes; a different customer on the same session is replaced. A rejected token (invalid, expired, revoked or replayed) logs the guard out. Requests without a refresh cookie, such as the CMS password form, are not touched. Signed-out account URLs now go to the sign-in page in the visitor's language with `?next=`; the sign-in page follows only same-origin `/customer/...` paths, once, with a loop guard. The booking form's sign-in redirect is localized the same way.
- **Validation:**
  - `tests/Security` (5 new session cases, 79/79).
  - `BW-Sahara-Sky-Frontend/tests/account-session.mjs`, against the real stack in three languages at 390 and 1280. It covers an expired Laravel session with a valid refresh cookie, a bookmarked account page, reload, back/forward, reopening the site, concurrent tabs, logout, back after logout, an invalid token, two isolated customers and switching customer, plus the empty state.

### 2. Brief burgundy when opening the site

Causes, kept apart:

- **Current release:** every page declared `<meta name="theme-color" content="#571c35">` (the old burgundy), which mobile browsers paint into the toolbar and status bar while the page loads. The contact-page icons also had burgundy SVG strokes. Both are now brown `#60452f`. The stylesheet itself had no burgundy colours, and there are no late JS colour changes or prerender/hydration colour differences: the test compares colours at DOMContentLoaded with colours after load.
- **Release transition:** a page from an older release, restored from the back/forward cache or left open in a tab, stayed on screen until the asynchronous manifest check reloaded it. This matches the old CSS filename seen in a tab. Each page now records its release. A restored page whose recorded newer release differs reloads immediately without waiting for the network; hidden tabs update in the background. Pages with unsaved input keep the existing "update available" notice instead of reloading. This only helps once visitors have loaded a release containing this change; pages from earlier releases cannot be changed retroactively.
- **Not verifiable locally:** HTML caching by the host or CDN. `_headers` asks for `no-cache` on HTML and `no-store` on `sw.js`/`build-manifest.json`; Staging should confirm that the host or LiteSpeed/CDN honours this.
- **Validation:**
  - `tests/initial-render.mjs`: built output, cold load, reload, concurrent tabs, back/forward, hidden tab and unsaved input; 151 checks.
  - `tests/release-update.mjs`, extended: runs the real runtime code in a VM, including the synchronous restore path, because headless Chrome did not use the back/forward cache.
  - `tests/cache-update.mjs`.

### 3. Customer website

| Issue | Cause | Fix | Test |
|---|---|---|---|
| Ticking an extra service or food left "Checkout" disabled for good when no payment method is enabled | `checkout.js` re-enabled the button only in the callback of `.load()` on `.list_payment_method`, which jQuery never calls on an empty selection | Re-enable when the list is absent | `tests/checkout-form.mjs` |
| A failed price recalculation left the button disabled without a message | error handler did not re-enable | Re-enable and show the error | same |
| Enter in the coupon field submitted the whole booking form | field inside the main form | Enter applies the coupon | same |
| Browsers could keep an old `checkout.js` | asset URL had no version | Versioned by file time | — |
| Saving the profile with an empty date of birth stored today's date | `Carbon::parse(null)` is "now" | Store blank when empty | `tests/Fixtures/profile-cases.php` |
| Breadcrumb "Home" link 31×14 px on mobile | text-only link | 24 px minimum target | — |

`checkout.js` has no build tooling in this checkout, so the same changes were applied to `assets/js/checkout.js` and to both compiled copies.

## Not changed (remaining issues)

- **Room categories for rooms outside the static catalogue:** the room API has no category, so the frontend files unknown rooms under "Standard", and in Arabic and Chinese shows a generic name for them. A correct fix needs the API to expose the category and translated names. This is an API change and needs approval.
- **`BookingRoom::scopeActive()`:** joins `ht_bookings` without a select list, so booking columns (`status`, `id`) overwrite booking-room columns. On every admin page that uses it, this logs "Value completed/processing is not part of BaseStatusEnum". Pages still work. The recommended fix is `->select('ht_booking_rooms.*')`, verified with the InnoDB availability tests, because the scope is used by inventory checks.
- **Admin Settings → General** returns 500: `$manageLicense` is undefined in the Botble license views. This is already documented; it is a licensing matter.
- **Booking reports** return 500 on SQLite only, because they use MySQL's `date_format`. They need checking on MySQL.
- **Refresh throttle:** every full page load calls `/api/hotel/auth/refresh`, which is throttled at 30 per minute per client. Very fast browsing (more than 30 pages a minute) would show as signed out until the next minute. This was not changed because it is a security setting.

## Hotel decisions

- Which payment methods (if any) are enabled at launch. The booking form now works without one, but the copy describing payment depends on it.
- Category and translated names for any room added beyond the current seven.

## Not tested

- Real payment gateways (a sandbox is needed), real email delivery, Google sign-in, MySQL-only reports, and the host's Apache/LiteSpeed/CDN caching.

## Running the checks

From `public_html`, on a local `*fixture*.sqlite` database only (the scripts refuse anything else):

```
php tests/Security/run.php
php tests/Fixtures/seed-account-fixture.php
php tests/Fixtures/dashboard-cases.php
php tests/Fixtures/profile-cases.php
php tests/Fixtures/seed-admin-fixture.php   # disposable admin users for local admin checks
```

The browser tests start the gateway with `HOTEL_BACKEND_ORIGIN`, set `HOTEL_TEST_URL` and run in `BW-Sahara-Sky-Frontend`:

- `tests/account-session.mjs`
- `tests/checkout-form.mjs`
- `tests/initial-render.mjs`
- `tests/account-header.mjs`
- `tests/release-update.mjs`

The fixture `.env` settings for the OTP sink are listed at the top of `account-session.mjs`.
