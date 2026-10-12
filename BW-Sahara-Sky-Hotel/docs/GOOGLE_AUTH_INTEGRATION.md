# Google sign-in in the integrated application

Google sign-in and email OTP use the same `ht_customers` identity and `HotelTokenService` access/refresh sessions. Existing Google links take precedence over changed email addresses. A Google identity cannot move to another customer, and unverified Google email addresses are rejected.

The connected frontend discovers enabled providers at `GET /api/hotel/auth/providers`. Its Google button opens `GET /api/hotel/auth/google?return_to=/login/` (Arabic, Chinese and booking review paths are also accepted). The existing Socialite callback remains `/auth/callback/google`, so the staging Google Cloud callback configuration does not change. The callback issues the existing HttpOnly, Secure, SameSite Strict refresh cookie; the frontend uses its existing refresh API. Tokens are never included in URLs or browser storage. Logout clears both the API token family and the CMS customer login.

## Staging setup

Keep staging's own `.env`, application key, database and Google client settings. Enable the hotel and social-login plugins and Google in Admin → Settings → Social Login, with that environment's client ID and secret. Credentials are not committed. Keep `APP_URL=https://staging.bwsaharaskyhotel.com` and `APP_ENV=staging`.

Build `BW-Sahara-Sky-Frontend` from the updated source with `HOTEL_SITE_ORIGIN=https://staging.bwsaharaskyhotel.com`, `HOTEL_INDEXABLE=0` and `HOTEL_BACKEND_MODE=same-origin`. Run `npm ci` then `npm run release`. Route `/api/hotel`, `/auth/callback/google` and `/admin` to PHP on the same origin; preserve authorization/CSRF headers and Laravel's `.htaccess`. Use the connected hosting guide. Do not deploy the standalone frontend `.htaccess` over Laravel's file.

The archives under `DEPLOY-READY-2026-10-12` predate this integration. Rebuild packages from source; do not upload those archives as the new complete release. Apply the existing integrated application's OTP/token migrations on the staging database before testing this bridge; there is no additional Google-specific migration in this change.

## Other recovered branch changes

- Ported the opt-in canonical-host middleware/configuration and safe trailing-slash rules. `CANONICAL_HOST_ENABLED=false` is the default. Nonproduction environments cannot redirect to the production hotel origin; auth, booking, admin and API paths bypass these redirects.
- Page sitemaps now exclude CMS pages marked `noindex`.
- Removed the tracked static PHP `public/robots.txt` override, preserving its contents in `docs/LEGACY_ROBOTS_BEFORE_DYNAMIC.txt`. Before any deployment that retires a server's custom static robots file, run the existing `scripts/migrate-robots.php` with its legacy, storage and backup paths. This preserves custom server rules. Decide which frontend/PHP sitemap owns the published public URLs when deploying the connected frontend.

## Validation and limits

Local isolated checks passed: 18 Google account/link/redirect assertions; 66 security/customer-session checks including the real token bridge, refresh and combined logout; 100 SEO checks including page sitemap eligibility; 50 canonical-host checks; 3 frontend deployment-configuration checks. PHP/JavaScript syntax and Git whitespace checks passed. Google unit assertions were invoked through the official PHPUnit PHAR using a local harness because Windows sandbox readability probes prevent normal PHPUnit discovery; dependency loading was adapted locally for the standalone suites. No test contacted Google, mail or payment services.

The earlier server-copy staging Google login was tested successfully for new/repeated login with the same customer ID. The integrated frontend and token bridge introduced here still need an HTTPS staging browser test after deployment: Google login in all languages, repeat login, checkout eligibility without payment, refresh and logout. MySQL concurrency and actual SMTP delivery were not retested in this task. No production deployment was performed.
