# Connected local preview

The frontend can run with or without Laravel. To enable the local same-origin gateway, set:

```powershell
$env:PORT = '8782'
$env:HOTEL_BACKEND_ORIGIN = 'http://127.0.0.1:8781'
node tools/serve.mjs
```

Laravel must already be running on that backend port. The gateway accepts only a loopback HTTP upstream and is a development server, not a production reverse proxy.

Connected URLs:

- Site: `http://127.0.0.1:8782/?lang=en`
- Real admin: `http://127.0.0.1:8782/admin/login`
- `/login/admin` redirects to the real admin login.

Published room listings/detail text, date/capacity/inventory availability, and contact enquiries are connected. The real Botble administration manages the same local database. Customer accounts, reservation creation, external Aiosell/payment flows and full CMS content synchronization remain separate work.

The PHP project report `docs/FRONTEND_BACKEND_INTEGRATION_2026-10-07.md` explains the isolated database and current scope. Private test credentials live only in that project's ignored `tmp` directory. Do not copy them into frontend assets, commits or deployment configuration.

Run browser checks against the local clone:

```powershell
$env:HOTEL_ADMIN_CONFIG = '<private local admin JSON path>'
$env:HOTEL_LIMITED_CONFIG = '<private local restricted-account JSON path>'
node tests/backend-integration.mjs
node tests/connected-availability.mjs
```

The integration test deliberately creates a marked contact record in the local clone. The local `verify-inbox.php` companion confirms the saved record and deletes only that test marker. Calendar exclusion is checked by `verify-availability.php`, which removes its temporary block in `finally`.

Never run these fixtures against production. The database and account preparation scripts refuse a database other than the isolated local clone.
