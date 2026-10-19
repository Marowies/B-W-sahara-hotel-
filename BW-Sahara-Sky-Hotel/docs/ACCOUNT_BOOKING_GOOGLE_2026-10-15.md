# Account, booking selection and Google sign-in

Base deployment: `5d26eecf`. Prepared locally; no server, database or production changes.

- Room-detail availability buttons infer the current catalogue room when the button has no ID. The chosen name is shown in the date dialog and search page. Available results promote that room and label it, while retaining alternatives. Old backend room IDs are cleared and rebuilt only from availability results. No room is automatically booked.
- The guest-account master no longer renders the photo upload form, modal, photo or Change photo button. Name and email remain. Existing stored photos and the backend avatar route are not deleted.
- The Google callback previously logged the customer out and explicitly requested an email OTP. Following the approved change, a verified Google identity now establishes the customer guard and the existing refresh-token family directly. Email OTP remains a separate sign-in method. OAuth state checking, verified-email and provider-ID resolution are unchanged.
- Ordinary Google sign-in redirects to the localized account overview. Explicit booking-review and validated account destinations are retained. No credentials, tokens or email addresses are placed in the return URL. Old pending-OTP state is cleared.

Validation: security regression harness using an isolated in-memory SQLite database; Google identity resolver tests (18 tests / 21 assertions); frontend guest journey regressions; staging frontend build and compressed deployment configuration checks.

Required after deployment: real Google consent/callback without a second email OTP; fresh email OTP remains usable; refresh and logout; ordinary sign-in -> account; booking return -> review; Triple detail -> date dialog -> availability -> matching room -> native form. Confirm the native form still displays the same room and dates. Confirm photo controls are absent from bookings/profile/reviews. No live payment or booking submission is part of local verification.

Unresolved content: checkout says departure at 12 AM and offers a 14-day cancellation refund, while public terms remain an approval draft. Hotel approval is required; no policy was invented or changed.
