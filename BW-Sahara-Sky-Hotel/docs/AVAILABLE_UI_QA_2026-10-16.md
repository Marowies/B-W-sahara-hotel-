# Available functionality review — 16 October 2026

Deployed baseline: `5ac339da`. Scope: guest UI and the available booking search, without final reservation, payment, email submission, profile mutation or staff-account changes. User reports sign-in working. The current review does not independently re-test Google or OTP delivery.

## Confirmed issue and local correction

Home quick-stay accepts equal arrival/departure values at the HTML field level, then correctly rejects them in the existing stay collector. However, its error status was `sr-only`, with a 1×1 pixel rectangle: sighted users saw no error and remained on the page. The correction makes this existing status visible and puts it across the search grid, hiding it only while empty. Booking/date validation rules are unchanged.

Verified locally in the browser after the staging-mode build: the error has a visible 23.8px height, stays visible without horizontal overflow at 390px, and changing departure to a later date continues to booking with two children preserved. Existing guest-journey and deployment configuration tests pass; 93 pages prerendered successfully. Screenshot: task workspace `outputs/quick-stay-visible-error-2026-10-16.png`.

## Verified live

- Home quick search carries 20–22 November 2026, two adults and two children into the booking search exactly.
- Two children return no matches; zero children return seven room types for those dates. The public catalogue reports `max_children: 1` for all seven rooms. This is consistent with current configuration, not evidence of a transport defect. Hotel must approve occupancy values; none were edited.
- Follow-up reproduction: 0/1 children each return seven types; 2/3 each return zero. A local follow-up adds an explicit capacity notice only when total availability is zero and the selected children exceed the maximum configured for any published room. The frontend explains this limit and links to hotel contact, preserving the entered guest counts. Ordinary no-availability messages remain unchanged. No capacity value or booking acceptance rule is altered.
- Standard category filter shows the three Standard room cards. No broken loaded room images in the inspected English/Arabic/Chinese room states.
- At a real 390px viewport, room pages in English, Arabic (RTL) and Chinese have no document horizontal overflow. Arabic navigation menu opens; language switching preserves the room-list page. This is one browser with a mobile viewport, not a physical iPhone/Safari test.
- Cookie preferences reopen, Necessary only closes the banner and remains remembered on ordinary reload.
- Gallery photo opens in its lightbox and closes. Contact form refuses empty required name/email/message fields. No real message was sent.
- Ordinary/Gzip/Brotli requests return identical decoded bytes for home, worker and build manifest. Worker and manifest return no-store; HTML requests revalidate. `/admin` returns 404 without a redirect; the configured staff login returns 200 and no-store.

Evidence: `outputs/qa-http-2026-10-16.json` in the task workspace. The review is a focused pass, not a guarantee that every possible state or browser is defect-free.

## Pending outside this pass

Approved policies and final editorial copy; hotel occupancy review; real reservation/payment and delivery setup; cross-customer authorization acceptance with separate identities; account/new design states requiring sign-in; actual mobile browser coverage; full production URL migration and Search Console data. No staging indexing or Production settings were changed.
