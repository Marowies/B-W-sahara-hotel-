# Website payment preparation — 7 October 2026

## Current decision

The owner intends to collect payments through the hotel website. The gateway has not been selected and there is no merchant or sandbox account. Stripe and PayPal below are existing adapters being repaired, not a recommendation or an approved choice for the Egyptian hotel.

**This is local payment hardening, not a completed or live payment integration.** The frontend still does not submit a paid reservation. Existing Aiosell bookings remain Pay@Hotel; this work does not change Aiosell settings or its missing payment-link delivery.

## Implemented locally

- Stripe Checkout creation now persists a pending payment and a unique session-to-payment binding before handing the checkout URL to the customer.
- Checkout return completion uses the stored booking, customer, currency and total instead of provider metadata or browser parameters. It verifies the retrieved session and PaymentIntent, including paid/complete status, exact amount received and charge identity.
- Signed `checkout.session.completed` and `checkout.session.async_payment_succeeded` events use the same verifier. Completion therefore does not depend on the customer returning to the hotel website. Unpaid sessions do not fulfill bookings. Events arriving before local persistence request retry.
- Row locking and a shared local completed state prevent return-page replay and subsequent intent webhooks from repeating the fulfillment hook. Exceptions in fulfillment roll back local status and charge updates for retry. These checks do not prove exactly-once delivery of external email side effects.
- Legacy Stripe direct-charge completion now requires a persisted local payment, exact provider amount/currency and a successful paid charge. Completion arguments cannot override the stored booking. Failed verification does not emit booking fulfillment.
- Minor-unit conversion no longer depends on the website's currency display precision. Excess fractional precision is rejected rather than silently truncated.
- Checkout line items must add up to the expected total before a provider request is made.
- Browser callback errors no longer expose Stripe exception messages.
- Hotel payment data requires the current server-side booking transaction identity, uses the booking's stored currency/customer, and does not add tax or subtract discounts twice. `Booking.amount` already contains both.
- Existing PayPal capture verification and signed Stripe intent webhook safeguards were retained.

## Database

Applied only to the existing local clone:

1. `2026_10_05_000002_create_payment_webhook_receipts.php`.
2. `2026_10_07_150000_create_stripe_checkout_bindings.php`.

The new binding table has unique checkout session and payment identities. No hotel prices, rate channels or inventory counts were changed. No production migration or deployment was performed. Checkout sessions created before this binding table was populated cannot be credited by a browser return; they require explicit reconciliation.

## Verification

**141/141 integration and regression cases passed**, including 32 new checkout/charge cases, on a fresh disposable loopback MariaDB/InnoDB database. Provider transport is replaced by local fixtures; signatures use a synthetic test secret.

- Six simultaneous signed Stripe intent deliveries: six HTTP 204 responses, one receipt and one fulfillment.
- Four simultaneous room checkouts: one booking, three availability rejections.
- Calendar/checkout races in both directions kept capacity at one.
- Checkout and intent identity, amount, currency, unpaid states, forged signatures, replay, refunded states and transaction rollback were exercised.
- A separate read-only check against the booted full CMS verified inclusive booking totals, stored currency/customer and rejection when checkout identity is missing.
- Migration rollback/reapply and Git whitespace checks passed.

Machine-readable evidence: `PAYMENT_PREPARATION_RESULTS_2026-10-07.json`.

## Remaining work before taking money

1. Select an eligible gateway and obtain its merchant/sandbox credentials. Implement and test that gateway's adapter if it differs from the existing Stripe/PayPal code. Decide between hosted checkout and an embedded provider form; card data must go directly to the provider.
2. Confirm the website rate column, room/night versus person basis, taxes/meals, settlement currency, deposit/full-payment policy and cancellation/refund policy. The supplied B&W rate column has not been confirmed as the direct website price and has not been applied.
3. Wire the new frontend booking flow to authoritative server quotes and inventory reservations. Add persistent initiation idempotency: current tests prevent duplicate fulfillment of the same payment, **not multiple separate provider charges or bookings created by repeated checkout initiation**.
4. Reconcile configured gateway fees. The existing Stripe hook calculates `payment_fee` separately without consistently adding it to the total; the new line-item check now rejects this inconsistency instead of sending an incorrect amount. Percentage fees and tax rounding need an agreed server quote/rounding policy before enabling them. No new fee was introduced.
5. Audit and test refund initiation, partial refunds, duplicate refund requests, amount/currency precision and refund webhooks with the selected gateway. Existing online refund code is not certified by these tests.
6. Test provider timeouts, browser abandonment, expired sessions, late successful payments, reservation expiry/release, reconciliation and admin financial states. Verify that a late payment cannot revive a cancelled booking or exceed current inventory; existing calendar/checkout race tests do not cover that lifecycle.
7. Define how website bookings and payment status reach Aiosell, without duplicate reservations or double collection. The external payment-link workflow remains unresolved.
8. Configure production HTTPS callback/webhook URLs, secrets and monitoring; run real sandbox success, decline, authentication, retry and refund tests. Then deploy approved changes and perform an agreed production smoke test.

## Provider references

- [Stripe checkout fulfillment](https://docs.stripe.com/checkout/fulfillment).
- [Checkout Session object](https://docs.stripe.com/api/checkout/sessions/object).
- [Stripe idempotent requests](https://docs.stripe.com/api/idempotent_requests): provider retry keys alone do not replace a persistent local attempt ledger; keys can be pruned after at least 24 hours.

No live charge, provider account change, Aiosell change, Git push or production publication was performed.
