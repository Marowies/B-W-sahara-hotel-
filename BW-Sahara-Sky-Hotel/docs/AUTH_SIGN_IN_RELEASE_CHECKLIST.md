# Customer sign-in: deployment and acceptance

Email and Google both require a six-digit email OTP before customer access or refresh tokens are issued. Google supplies a verified email address, then the shared OTP delivery and verification flow completes sign-in. Customer guards must stay logged out until that verification succeeds.

## Configuration

- Google credentials belong in the CMS social-login settings. Never commit secrets or put them in URLs or frontend code.
- Register the exact callback for each enabled environment in Google Console:
  - Local: `http://127.0.0.1:8782/auth/callback/google`
  - Staging: `https://staging.bwsaharaskyhotel.com/auth/callback/google`
  - Production: `https://www.bwsaharaskyhotel.com/auth/callback/google` if this is the canonical deployed origin.
- Configure a real delivery transport. Logging, array and failover transports containing testing sinks are rejected in every environment.
- Gmail: `smtp.gmail.com`, port `587`, STARTTLS, hotel Gmail username and an App Password. Do not use the OAuth client secret as an SMTP password.
- SMTP certificate validation stays enabled. TLS is required when the configured transport requests TLS/SSL.
- The local credential entry file is `tmp/local-integration-20261007/private-smtp.json`, which is ignored by Git. Its password field is intentionally empty until the owner fills it.
- The local configuration helper validates the fixed Gmail destination, authenticates over verified TLS without sending a message, and only then saves settings and removes the temporary credential file. It refuses any non-local database.

## Verification required before release

1. Confirm Google provider readiness and a redirect to Google's authorization endpoint, without exposing secrets or state tokens in logs.
2. Complete Google authorization using an owner-controlled test account. Confirm the callback retains OAuth state and does not authenticate the customer before OTP verification.
3. Request an OTP for the test mailbox and verify actual inbox delivery. Also verify a wrong code, expiry, resend cooldown and one-time consumption.
4. Confirm Google callback delivery failures create no customer session. Reloading the OTP screen must not reset resend cooldown, and expired pending Google state must disappear.
5. Confirm payment/booking eligibility remains denied before verification, then permitted for the verified customer only.
6. Confirm refresh rotation, logout, token-family revocation, absolute expiry and token garbage collection remain valid.
7. Deploy the reviewed source changes and configure credentials separately on staging. Repeat real delivery and Google callback checks there before production.

## Current evidence and limits

The standalone security regressions pass, including Google OTP gating, delivery-failure rejection, pending-state expiry, refresh/logout and booking protection. SMTP TLS configuration was checked without saving settings or sending a message. Local Google provider readiness and initial authorization redirect were verified.

On 14 October 2026, verified TLS authentication succeeded for the owner-authorized temporary sender `marawanewis22@gmail.com`. The local CMS now uses SMTP and the OTP request endpoint returned HTTP 200 with `sent: true` for a test sent to the same mailbox. The temporary credential entry file was removed after successful configuration; credentials remain outside Git in local settings.

The owner confirmed inbox receipt of the test OTP on 14 October 2026 and confirmed that the App Password belongs to the configured sender. Real automatic email delivery is verified locally. A complete real browser sign-in, external Google authorization and Google Console callback registration have not yet been certified. Local changes and local credentials are not a production deployment.
