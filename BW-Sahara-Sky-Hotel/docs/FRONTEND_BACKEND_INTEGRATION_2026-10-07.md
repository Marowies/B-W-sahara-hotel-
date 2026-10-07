# Local frontend/backend integration — 2026-10-07

## Implemented scope

The `new-frontend` design is connected locally to the real Laravel/Botble application on the `backend` branch. This is the first integration phase, not a completed production release.

- Frontend: http://127.0.0.1:8782/?lang=en
- Real administration: http://127.0.0.1:8782/admin/login
- PHP application: loopback port 8781.
- Independent MariaDB clone: loopback port 24407, database `hotel_local_frontend_20261007`. The previous database and backups remain intact.
- Local test-admin credentials are stored in ignored `tmp/local-integration-20261007/private-admin-access.json`. Do not commit or deploy this account/configuration.

## Data flow

The Node preview gateway forwards `/admin`, `/api/hotel`, `/vendor`, and `/storage` to Laravel on the same origin. `/login/admin` redirects to the real admin login. No password is implemented or verified by the static frontend.

The gateway injects a backend-enabled HTML meta tag. Standalone design previews retain their existing preview behavior. Connected pages read published room names, descriptions, capacities, sizes, bed counts and base prices from Laravel. Existing design photography is matched by the room slug; image editing/media synchronization is a later phase. Missing room currency falls back to the hotel's default currency.

Availability calls the existing `GetRoomService::getAvailableRooms`, which checks guest capacity, daily inventory, active reservations and calendar blocks. Merely listing published rooms is not sufficient for availability. Connected searches suppress cached demonstration results until the API responds.

Contact enquiries are validated and saved into the real CMS contact inbox. They do not send external email or WhatsApp during local testing.

## Authentication, validation and caching

- Real admin authentication and permission middleware remain in place.
- Contact writes use the Laravel session CSRF token, validation and a five-request/minute throttle. Public API reads have a 60-request/minute throttle.
- New API token-mismatch errors return HTTP 419; the existing legacy admin response contract remains unchanged.
- Public room responses expose an explicit allowlist of fields, not complete database models.
- Admin/API/session responses are `no-store`. New service workers exclude admin, API, vendor and storage paths; the fresh local origin avoids old preview service-worker caches.
- Frontend navigation does not intercept admin links.
- Local mail uses the log driver. Payment methods are disabled in the isolated clone. Local cookies are HTTP-only and SameSite Lax; production HTTPS cookie settings must be configured separately.
- Local remote font downloading and automatic CMS update checks are disabled; the administration uses bundled brand fonts. This avoids waiting for external font downloads during dashboard rendering.

## Verification

Automated browser checks live in the frontend repository at `tests/backend-integration.mjs`; results are saved under its ignored `test-results` directory. Account paths are passed through `HOTEL_ADMIN_CONFIG` and `HOTEL_LIMITED_CONFIG`; credentials are never printed by the test.

Verified: published catalogue with seven records, invalid-date rejection, guest protection, CSRF rejection, actual admin login, branded dashboard, authenticated room administration, frontend cards from backend records, contact POST and administration inbox access. A separate database check confirmed the submitted contact existed and removed only the matching test record. A temporary calendar block was excluded from API results and removed in `finally`. PHP syntax checks passed for the new controller and amended exception handler.

The final browser run passed all 12 grouped checks with no JavaScript page errors. It also opened the booking list, room editor, pages and galleries; confirmed the contact inbox is accessible; verified mobile overflow and compact logo dimensions; and verified that an activated staff account without permissions cannot read rooms, bookings or contacts. Botble denies HTML access by redirecting to the dashboard and JSON access with HTTP 401; both behaviors were checked. A further frontend test passed four availability/UI/cache checks. The oversized mobile admin logo was corrected in the brand stylesheet. These checks do not certify every admin write operation or production security.

## Remaining integration work

- Customer sign-in, registration and password recovery still use design previews.
- Booking review/confirmation do not create a reservation. Availability results explicitly explain this.
- External Aiosell availability/reservations, Banque Misr/payment links, payment callbacks and WhatsApp delivery are not connected or tested in this phase. Local database inventory is not proof of Aiosell inventory synchronization.
- Static editorial, service, gallery and home content is not yet fully driven by CMS records. Admin editing those modules does not update every design page automatically.
- Database room content currently uses its stored language; multilingual database-content mapping is still pending.
- Production routing, HTTPS, shared deployment, backups and final end-to-end reservation testing remain required. Nothing has been pushed or deployed by this integration task.

## Local startup

The runtime is already running. Frontend startup from its repository:

```powershell
$env:PORT = '8782'
$env:HOTEL_BACKEND_ORIGIN = 'http://127.0.0.1:8781'
node tools/serve.mjs
```

PHP uses an ASCII temporary junction to the project because the portable Windows runtime failed to load extensions through the Arabic path. The local PHP configuration and router are stored in ignored `tmp/local-integration-20261007`; use the matching ASCII junction paths rather than modifying machine-wide PHP settings. The database requires its dedicated datadir and port; do not point this setup at the old staging or production database.

