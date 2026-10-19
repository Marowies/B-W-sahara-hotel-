# Remaining cache and General settings fixes

## General settings
The parent license partial computed `$manageLicense`, but the anonymous license component did not declare or receive it. Rendering the previous component reproduced the undefined-variable exception. The component now declares a false default and the caller passes the authenticated user's existing permission. Its input fields remain disabled without permission. A hardcoded “fully activated and verified” success message was removed from the activation form; no license verification, activation route, permission policy or license service was bypassed or changed.

Fixture rendering passed with missing, false and true permission props, including checks on buyer/purchase/checkbox disabled attributes. The previous component failure was independently reproduced before the change. Full General page acceptance remains necessary after deployment.

## Stale public documents
The worker checked backend path exclusions before checking for document navigations, so static booking documents bypassed the network-only branch. Navigations now use network with no-store before those exclusions, while private API and asset requests remain untouched.

The runtime previously checked the manifest only on BFCache restore or visibility/controller events. It now checks on ordinary page load too and compares referenced fingerprinted scripts/styles, so CSS/copy-only releases are detected even if runtime.js is unchanged. Unsaved input still produces an update notice instead of an automatic reload.

Tests exercise initial load, CSS-only stale assets, offline behaviour, back/forward, hidden tabs and unsaved input, as well as the real generated worker's booking/account/admin navigation handling. Legacy documents that predate all of these mechanisms may need one reload to acquire the current runtime/worker; there is no promise of retroactively changing code in already-open old documents. Browser acceptance is still required on Staging.

No database changes, mail/payment configuration changes or deployment in this commit.
