# Customer UI locale — local migration and API

Applied only to the isolated `hotel_local_frontend_20261007` database on 2026-10-07. Production data and payment configuration were not changed.

Migration: `public_html/database/migrations/2026_10_07_120000_add_ui_locale_to_hotel_customers.php` adds nullable `ht_customers.ui_locale` (maximum five characters). Existing customers retain null; no backfill invents a preference.

`POST /api/hotel/locale` accepts only `ui_locale: en|ar|zh`, uses the authenticated `customer` guard, and writes only that customer. The route has web-session CSRF protection and a 10/minute throttle inside the existing 60/minute hotel API group. Guest/administrative authentication does not substitute for a customer session. `GET /api/hotel/session` also returns the current customer's saved preference, or null.

The frontend cookie records explicit choice immediately. Its language control waits for the best-effort customer preference save before changing to the equivalent native URL, with bounded request timeouts. A failed save does not block navigation; anonymous users retain cookie preferences. Customer account authentication itself remains a separate integration scope.

Verified locally: guest rejection, all three locales persisted, invalid input rejected without changing the saved value, and actual HTTP 419 without CSRF / 401 for a guest with a valid session token. Authenticated persistence was verified using a transaction-scoped synthetic customer rolled back afterwards; no permanent guest/customer fixture was left behind.

Deployment order: apply the migration on the intended backend before exposing the locale endpoint and connected frontend. This document is a handoff, not evidence of production migration. Rolling down removes saved language preferences; it does not modify booking, availability, invoice or payment records.
