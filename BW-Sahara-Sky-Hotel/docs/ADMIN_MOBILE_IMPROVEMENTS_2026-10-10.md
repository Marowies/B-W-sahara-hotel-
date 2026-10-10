# Admin mobile interface — 10 October 2026

## Scope

Improved the real Botble admin interface served by the local application at
http://127.0.0.1:8782/admin. Changes are local and have not been deployed.
Controllers, permissions, payment handling and hotel records are unchanged.

## Improvements

- Mobile navigation opens as a bounded, scrollable menu with a close button,
  backdrop, keyboard focus containment and Escape support. The underlying
  page is inert while the menu is open. Desktop navigation remains intact.
- Mobile buttons and navigation controls have at least 44px touch targets.
  Form inputs use 16px text to avoid iOS input zoom.
- Dashboard summary tiles use two columns on phones.
- Wide tables scroll within their own containers, with a scrolling hint and
  keyboard focus support, instead of widening the whole page.
- Media uses two columns on phones, with separate search and toolbar rows.
  Each folder has an explicit open button that invokes the existing native
  folder handler. Selection and bulk operations remain available.
- Form action bars move to the bottom on mobile, with safe-area spacing.
  Dialogs and uploads are bounded by the viewport.

## Assets

- `public_html/public/vendor/core/core/base/css/bw-brand/bw-admin.css`
- `public_html/public/vendor/core/core/base/js/bw-admin-mobile.js`
- Admin layout header, vertical sidebar partial, and media view.
- The frontend repository's admin asset copies are synchronized.

The layout uses CSS version 3 and mobile JavaScript version 1 so existing
browser caches fetch the new files. No frontend bundle rebuild is required
for the PHP admin served through the local gateway.

## Validation

The dashboard, media, rooms and bookings pages were checked at widths
320, 390, 768 and 1440px: all 16 page/viewport combinations had no horizontal
page overflow. Updated 390px dashboard/media screenshots were reviewed.

The browser test is `BW-Sahara-Sky-Frontend/tests/admin-mobile.mjs`, runnable
with `npm run test:admin-mobile` while the local gateway and database are up.
It reads the private local test administrator fixture; never use production
credentials. It opens forms without saving records and blocks external
requests. Therefore unavailable external thumbnails in its screenshots do
not establish a missing-image defect in the application.

Screenshots and interaction results are under
`BW-Sahara-Sky-Frontend/test-results/admin-mobile/`.

Initial test runs encountered browser network suspension or timed out while
waiting for network idle in media. The test now waits for loaded controls
instead. See `results.json` for the completed interaction checks.

Completed checks across the runs: 16 page/viewport checks, 5 mobile navigation
and media checks, and 3 room editor/RTL/JavaScript checks. The room editor also
encountered a local gateway upstream timeout once; its isolated retry passed.
The local gateway timeout was not increased and no production hosting setting
was changed. Both mirrored asset pairs have matching SHA-256 hashes.
