# SEO implementation — 2026-10-07

Branch `seo/strategy-and-implementation`. Each batch below is committed separately. Verification uses the standalone runner `public_html/tests/Seo/run.php`, which loads the working-tree SEO classes, hotel controllers and theme templates without a database or production settings. Behaviour on the live site (rendered HTML, Search Console) has not been verified; each item is **implemented — runtime verification pending** unless stated otherwise.

```powershell
cd public_html
php tests/Seo/run.php
php tests/Security/run.php   # booking/payment/security regression suite
```

## Batch 1 — canonical and indexability

Before this batch, Botble's SEO helper rendered a canonical only on CMS pages and blog posts. Rooms, room categories, services, places, foods and the rooms listing had **no canonical**. Service and food pages also skipped `BASE_ACTION_PUBLIC_RENDER_SINGLE`, so the per-item SEO title, description and index/noindex fields saved in the admin were never applied. Booking, checkout and customer-account pages had no robots directive.

| URL type | Classification | Mechanism |
| --- | --- | --- |
| `/rooms` (with or without search parameters) | INDEX | canonical `route('public.rooms')`; query strings stripped by the SEO helper |
| room, room category, place, service, food detail | INDEX when published | self-referencing canonical from the model's localized URL; admin SEO meta applied |
| any of the above while draft/pending | NOINDEX | hook at priority 40 after the SEO helper (56) |
| `booking/{token}`, `checkout/{transactionId}` | NOINDEX | route-name listener; booking/payment logic untouched |
| `customer/*`, login, register, password reset | NOINDEX | same listener |
| blog search `public.search` | NOINDEX | same listener |

Food is now registered with the SEO helper so it receives the same admin SEO meta box as the other hotel content types.

Not changed:

- **robots.txt.** `public/robots.txt` can be edited from the Botble admin, so production may differ from the repository copy. The repository copy contains WordPress-only rules (harmless, irrelevant) and the production sitemap reference. No private page was added to `Disallow`, because crawlers must be able to fetch pages to see their `noindex`.
- **Unpublished detail pages still return 200.** They are noindex, but returning 404 to the public would also change admin preview behaviour. That decision belongs to the owner.
- **The iCal export (`ical/{slug}`)** is a non-HTML calendar feed that may be consumed by channel managers. An `X-Robots-Tag` header was not added, because that controller belongs to the protected inventory-synchronization area.
