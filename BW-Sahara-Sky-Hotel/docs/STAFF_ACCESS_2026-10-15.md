# Guest presentation and staff access

The approved default keeps the existing `/admin` administration entry point, separate from customer navigation. A different path is not an authorization control. No admin route, password, account, permission, environment setting or database value is changed by this patch.

The active riorelax theme calls the CMS AdminBar presentation switch (`setIsDisplay(false)`). This prevents its editing toolbar and its admin links from being appended to theme-rendered guest pages, including account and booking pages, even when the browser also has a staff session. The dashboard itself is unchanged. Static frontend pages already omit the toolbar. This is a rendering change, not a CSS cover-up or a privilege change.

Customer email OTP and verified Google sign-in use the `customer` guard and hotel customer token service. They do not issue staff permissions. Staff keep their existing administrative login. No Google admin login is enabled.

## Proposed staff roles (not created or assigned)

| Role | Intended access | Exclude by default |
| --- | --- | --- |
| Owner / administrator | Staff accounts, roles, configuration and operational oversight | Reserve for named owners; no shared account |
| Reservations / reception | Bookings, availability and customer details needed to service stays | Staff management, SMTP, gateways, plugin/theme installation |
| Content editor | Rooms' descriptive content, images, articles and approved public pages | Booking/customer records, payments, system settings |
| Finance | Booking invoices, payment records and reports needed for reconciliation | Staff management, gateway credentials, theme/plugin code |

Permission names and overlap must be checked against the installed CMS before creating the roles; use least privilege rather than broad administrator grants. Each employee needs their own named account. Owner supplies name, work email and required role. Existing accounts must not be removed, reset or restricted until a replacement owner login has been verified.

## Staging checks after applying

- Staff signed in: account, bookings, room details and native booking form show no admin toolbar, no toolbar stylesheet and no toolbar spacing.
- Customer-only and signed-out visitor: same guest interface, with no administration links.
- `/admin` remains accessible through its normal staff login and retains its existing authorization.
- Customer Google/OTP still leads to the account or the booking in progress; a customer login alone cannot access the administration dashboard.

Local verification: PHP syntax and confirmation that the CMS footer hook checks `AdminBar::isDisplay()` before appending toolbar markup/styles. Live verification remains required after the manual staging deployment.
