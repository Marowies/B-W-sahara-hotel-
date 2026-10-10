# Admin and public layout fixes — 10 October 2026

Implemented in the local working copies of BW-Sahara-Sky-Hotel and BW-Sahara-Sky-Frontend.

## Changes

- Record names now have the highest DataTables responsive priority. On narrow screens, a category/room name remains visible instead of being displaced by translation icons and operations. Selection remains available; hidden translations and operations are accessible in expanded row details.
- Shared admin card styles let grid/flex children shrink, wrap long headings, labels, hints and buttons, and keep form controls within their cards. Operational headings and breadcrumbs use readable interface typography.
- Phone permalink fields place the URL prefix on a separate row, leaving a usable slug input. Preview URLs wrap instead of being truncated.
- Media folder titles use the full tile width with the folder-open control below them on small screens.
- Table overflow hints are recalculated when table dimensions change, including asynchronous responsive layouts.
- Public room cards, forms, footer tracks and pagination wrap within their own containers. Narrow room cards stack actions when necessary; long multilingual headings wrap.
- Responsive image enhancement clears its old source set when an image changes to an unlisted/backend source. This prevents the browser from continuing to display the previous room photo.
- Updated admin CSS/JS version parameters and rebuilt the frontend assets and 93 prerendered language pages. Build version: `3f235082174c`.

## Verification

All 49 checks passed:

- `tests/ui-layout.mjs`: 28 checks. Public room catalogue, Arabic catalogue and contact page at 320, 390, 768 and 1440 pixels; card/form/footer bounds; room image replacement; visible admin record names; expanded translation/operation controls; room creation and hotel general settings; long unbroken heading; usable phone slug input; no browser JavaScript errors.
- `tests/admin-mobile.mjs`, quick mode: 12 checks. Dashboard, media, rooms and bookings at 390 pixels; mobile menu/background isolation/Escape/close button; opening folders with one tap; media filters; room editor; RTL layout; no browser JavaScript errors.
- `tests/local-admin-assets.mjs`: 9 checks. Compression, cache invalidation, conditional/HEAD requests and exclusion of private/admin/API paths.
- PHP syntax validation passed for both changed shared table column classes. Node syntax validation passed for the changed runtime and admin script.
- Visual inspection included the actual 320-pixel room card, contact form and expanded admin category row. Asynchronous tables/images were allowed to load before assessment.

Frontend screenshots and machine-readable results are in `BW-Sahara-Sky-Frontend/test-results/ui-layout/`; mobile interaction results are in `test-results/admin-mobile/`. These are local test artifacts.

## Scope

This validates the listed local pages and interactions, including shared rules used elsewhere. It does not constitute a visual audit of every plugin page or a production deployment. Payment logic and hotel records were not changed. Existing source-image resolution and unavailable original media assets remain separate content issues; placeholders do not recreate missing originals.
