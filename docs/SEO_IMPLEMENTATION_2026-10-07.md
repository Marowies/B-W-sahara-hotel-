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

## Batch 2 — multilingual hreflang

Botble's language plugin adds hreflang (and its language-switcher URLs) from `AddHrefLangListener`, which listens for `RenderingSingleEvent`. CMS pages, blog posts and the homepage dispatch that event. The hotel plugin's own routes did not, so **rooms, room categories, places, services, foods and the rooms listing had no hreflang**. The hotel controller now dispatches the event from those six actions. Booking and checkout actions do not, so private pages never join a hreflang cluster.

How the cluster is built (Botble behaviour, unchanged):

- Hreflang values come from each language's database `lang_code`, lowercased and hyphenated. An `xx_YY` code also emits the bare language (`en_US` → `en-us` + `en`, `zh_CN` → `zh-cn` + `zh`). No mapping was hard-coded, because the live language rows are not in the repository.
- Targets use the translated slug from `slugs_translations` (Language Advanced) when one exists. Otherwise they use the same slug under each locale prefix. Each URL is absolute, and the current language's entry is self-referencing.
- `x-default` points to the default-language URL. The Botble seeder hides the default prefix (`language_hide_default = 1`; production setting not verified), which makes x-default the unprefixed English page. That is the site's language-neutral entry point, so it was kept.

`<html lang>` printed `app()->getLocale()` verbatim. A locale stored as `zh_CN` would therefore render the invalid tag `lang="zh_CN"`. It now renders the BCP 47 form (`zh-CN`). `en`, `ar` and `zh` are unchanged. Arabic `dir="rtl"` on `<body>` is untouched.

Open items (runtime/owner):

- **Chinese locale values — UNKNOWN.** The theme's stored option keys use `zh_CN` (`BookingEngineSettingsSeeder`), and Laravel's translation folder is `lang/zh`. The real `lang_locale` / `lang_code` / URL prefix must be read from the production `languages` table before confirming that the rendered values are `zh-cn` and `/zh/…`. Nothing depends on guessing them.
- **Translation equivalence — REVIEW REQUIRED.** Language Advanced falls back to the default-language text when a field is untranslated. A `/zh/…` alternate may therefore be English content (partial equivalent). The project review already found English room names on `/ar/rooms`. Fill the translations rather than removing alternates.
- **Listing canonical prefix — LIKELY.** `route('public.rooms')` is expected to carry the active locale prefix, because the language plugin prefixes the public route group. Confirm in rendered HTML on `/ar/rooms` and `/zh/rooms`.
