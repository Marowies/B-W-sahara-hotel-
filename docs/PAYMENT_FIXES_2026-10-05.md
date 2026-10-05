# Stored payment bindings and webhook replay protection — 5 October 2026

Base: `backend` at `1979e1f559ef6e76f92fe31791cd089a063187c1`. Work is isolated from `main`; no live site, production database, real provider API, email or WhatsApp was used.

## Reproduced before changes

The provided 84-case integration runner reproduced **80 PASS / 4 FAIL** on PHP 8.3.35 and MariaDB 11.8.9/InnoDB with its synthetic minimal schema. The four failures were IT-07 through IT-10: PayPal token mismatch, client-controlled completion fields, duplicate Stripe fulfillment and Stripe amount mismatch. All 77 non-payment cases passed. Security-only: **32/32**. Security plus backend regressions on SQLite: **49/49**.

Dependencies were installed from the existing Composer lock with scripts disabled. No broad update or dependency version change was made. Composer and portable runtimes were downloaded from their official distributions and checksum-checked. Generating the initial autoloader without optimization avoided an expensive scan of unused Google service classes; the repository's Composer configuration and lock were restored unchanged.

## Repairs

* PayPal creation persists one hotel booking's expected amount, currency, customer and provider order ID as a pending local payment. Completion does not consume amount, currency, booking or customer fields from the return request; these are removed from the generated return URL. Callback validation now requires token/PayerID rather than money supplied by the browser.
* PayPal checks the session order against the callback before any provider call, requires one unambiguous stored PayPal payment, and checks the returned order ID, completed capture identity/status, actual capture amount and currency. A stable capture request ID supports provider idempotency. Only verified service state can reach completion; a fingerprint prevents changing the binding between verification and the locked completion transaction.
* Stripe verifies the raw signed body with the existing SDK, checks the actual PaymentIntent identity (including an existing legacy charge binding), payment channel, status, amount and amount_received in minor units, and currency. A missing local binding returns 503 for retry instead of silently dropping an early event. Invalid signatures/payloads return 400; business mismatches remain unpaid and return 422.
* Add `payment_webhook_receipts`, uniquely keyed by provider/event ID. Receipt insertion, the locked pending-to-completed transition and the local fulfillment hook share a transaction. Duplicate event delivery and separate success events for an already-completed intent do not repeat fulfillment. Failed/refunded states are not overwritten. A database/hook failure rolls back the receipt and status for retry.

The single-booking restriction matches the hotel hook. This is not a new multi-order commerce integration. Existing PayPal orders initiated before this change do not have the new persisted expectation: drain or reconcile them explicitly before a future rollout; never restore the old query-field fallback.

## Verification after changes

**109/109 PASS** on the same InnoDB laboratory, including all original named cases and 25 additional cases. Tests cover creation and authoritative completion, provider and callback mismatches, changed bindings, replay, late events, invalid/expired signatures, currency/received amount/status/channel checks, zero-decimal Stripe money, legacy charge identity, refunded states, rollback/retry and migration rollback/reapply.

Six PHP processes sent the same signed Stripe event concurrently through separate InnoDB connections: all returned 204, with **one durable receipt and one recorded local fulfillment**. The hook is represented by a local recorder; this does not establish exactly-once external email or financial settlement. External side effects cannot be rolled back by a SQL transaction and need a reviewed queue/outbox policy during real integration.

Security-only and SQLite backend regression remained **32/32** and **49/49**. All changed/new PHP files passed syntax checking; diff whitespace checks passed. The isolated runner now supports `HOTEL_SECURITY_ONLY` and `HOTEL_TEST_RESULT_PATH` so new evidence does not overwrite the historical reports. Gateway enum registration is represented in the harness, as gateway providers extend that enum in the application.

```powershell
# From public_html, using a new, empty, disposable InnoDB hotel_test_ database on 127.0.0.1
$env:HOTEL_TEST_RESULT_PATH = 'D:\local-output\payment-regression.json'
php tests/Integration/run.php vendor/autoload.php 'D:\private-local\test-db-config.php' calendar-fixes
php tests/Security/run.php
$env:HOTEL_SECURITY_ONLY = '1'
php tests/Security/run.php
```

## Release and integration limits

Apply the additive receipt migration before deploying the new Stripe handler. Its rollback deletes replay history, so it is not a routine response to an operational failure. Validate schema changes against a fresh private staging copy before any deployment.

This work proves the tested internal PayPal callback and Stripe webhook paths. The complete Stripe Checkout success/direct-charge/refund flows, ownership/booking-policy checks, provider retries outside their idempotency retention, live capture, settlement and all CMS/admin routes still require sandbox/staging tests. Both existing PayPal SDK packages remain abandoned; no unsupported SDK migration or financial sign-off is implied by local success.

The full CMS boot was attempted locally, but the Windows execution sandbox rejected Laravel's atomic cache-file rename (and its normal directory writability check). Portable tools and the isolated backend runner work; a complete CMS installation/HTTP deployment was **not verified**. The performance report therefore explicitly uses a service laboratory, not a full CMS benchmark.

Aiosell Pay@Hotel and the separately generated Bank Misr payment links are a separate operational integration. Required inputs: hotel's agreed collection workflow; who creates/sends links; booking/payment reference and amount/currency matching; notification/update mechanism; provider documentation and sandbox access. No production keys are needed in this chat. Link delivery and settlement were not tested or fixed by these internal code changes.

Provider references: [PayPal Orders API](https://developer.paypal.com/api/orders/v2), [Stripe webhook delivery and duplicates](https://docs.stripe.com/webhooks), [Stripe currency units](https://docs.stripe.com/currencies).
