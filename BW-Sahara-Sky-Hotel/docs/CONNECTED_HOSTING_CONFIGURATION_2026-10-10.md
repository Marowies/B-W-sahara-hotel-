# Connected deployment configuration

These are configuration preparations, not a deployment to the hotel's hosting.

## Frontend build

Export the variables in `BW-Sahara-Sky-Frontend/.env.production.example` in the build environment. The file is a template; the build does not automatically load dotenv files.

- `HOTEL_SITE_ORIGIN`: the approved bare HTTPS origin, currently configured as `https://bwsaharaskyhotel.com`.
- `HOTEL_INDEXABLE=1`: for the approved production release only. Preview/staging builds must remain non-indexable.
- `HOTEL_BACKEND_MODE=same-origin`: enables the hotel API in the published HTML after prerendering, including Arabic and Chinese. This prevents build-time API errors being serialized into static pages.
- Run `npm ci` and `npm run release`; supply `HOTEL_BROWSER_PATH` if the build environment uses an installed Chromium rather than Playwright's browser.

The production server must serve static frontend pages/assets and route `/api/hotel/*` and `/admin/*` to the Laravel application on the same HTTPS origin. A static-only deployment cannot perform those functions. `tools/serve.mjs` remains a development gateway.

## Existing PHP hosting

Confirm the hosting provider's document-root and web-server features before deployment. For an Apache/LiteSpeed configuration:

1. Keep Laravel's `public/index.php`, its authorization/XSRF rewrite handling, existing uploads and vendor assets. Never publish the application root, `.env`, database dumps, private test fixtures or `tmp`.
2. Copy only the approved built frontend public pages/assets into the public document root. Do not replace Laravel's `.htaccess` with the standalone frontend `.htaccess`: its static-only configuration does not provide the complete connected application routing.
3. Configure `DirectoryIndex index.html index.php` for the public landing page, preserve Laravel's front-controller fallback, and explicitly retain Laravel handling for API/admin paths. Apply canonical locale redirects without redirecting POST/API requests. Verify this on staging; the local Node gateway is not evidence that the host's rewrite rules work.
4. Decide the authoritative robots/sitemap and legacy URL redirects for the new public frontend before publishing. Export and migrate existing custom robots rules using the supplied migration tool before removing a legacy static override. The PHP SEO branch and standalone frontend generate metadata for their respective views; avoid publishing parallel public URL sets without an approved redirect/canonical map.
5. Cache only hashed static assets. Keep HTML and `sw.js` revalidated; bypass authenticated/admin/API responses and responses carrying cookies or `no-store`. Preserve the previous release's hashed assets during rollout.

## Laravel configuration

`public_html/.env.production.example` documents required settings without production secrets. Supply a valid production application key, database credentials and SMTP account on the host. Do not copy the local clone's key, accounts, mail-log driver or database configuration.

Use production mode with debug/installer disabled, HTTPS and secure session cookies. Refresh cookies remain HttpOnly, Secure in production, and SameSite Strict in `HotelTokenService`. The ordinary CMS session cookie remains SameSite Lax for its existing behavior.

Tracking defaults to `off`. Configure either direct GA4 or GTM, with real IDs and explicit analytics consent. The merged tracking code prevents simultaneous direct and container ownership; it was tested without contacting Google.

After a production backup and schema review, apply the approved migrations, cache configuration/routes, and configure:

```text
php artisan schedule:run     # every minute through the hosting scheduler
php artisan queue:work --queue=security,default
```

If the plan does not support a persistent worker, agree a supported scheduler/worker arrangement with the provider and verify delivery, retries and failed jobs. The daily `hotel:prune-tokens` task is already declared in Laravel; a declaration alone does not run it.

## Required staging acceptance

- All three languages, canonical/hreflang/404 behavior and old URL redirects.
- Real admin login/permissions and the current mobile layouts.
- Real Gmail OTP delivery, refresh/logout over HTTPS, cookie flags, expired/reused sessions and queued security notifications.
- Catalogue, inventory/calendar, contact inbox, content/media updates and the intended non-payment reservation/external-engine path.
- Approved room information and rate-column mapping; the local catalogue is not automatically the final approved rate sheet.
- Hosting load/performance, private-response cache bypass, monitoring, backups and a restore/rollback exercise.

None of these host-level changes were applied to the live hotel in this task.
