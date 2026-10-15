# Quiet guest account presentation

Based on `seyam/site-ui-fixes` at `8616b1cd`. Presentation only; no changes to customer authorization, booking queries, profile saves, payment logic or routes.

- Sand header and brown typography match the public site palette.
- Compact navigation replaces the large left column on account pages.
- Overview removes the duplicate avatar/name/email sidebar. Avatar controls remain on the other account pages, and the existing avatar modal is preserved.
- Short welcome, main booking section and secondary profile section replace the large statistics cards. Counts remain available as a small summary row.
- Existing next/latest/empty booking selection, booking and payment status, account links and profile fields are retained.
- Changes are scoped to customer pages; the booking shell retains its existing presentation.

Validation: actual Blade templates rendered against existing disposable SQLite fixture customers (read only), for upcoming, empty and cancelled/latest states in English, Arabic and Chinese. All 18 sampled combinations at 390px and 1280px had one h1, no page overflow, no duplicate profile sidebar; Arabic used RTL. Preview includes the hotel customer stylesheet and the modified guest stylesheet. Currency control is a placeholder, avatar modal is hidden, and live page scripts/global theme assets are not reproduced in the static preview. This verifies layout, not end-to-end account functionality. Staging verification is required after a separately authorized deployment.

No merge, deployment, database record edits or server configuration changes.
