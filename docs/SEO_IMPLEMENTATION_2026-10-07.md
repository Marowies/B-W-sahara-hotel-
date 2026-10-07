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

## Batch 3 — sitemap coverage

The hotel plugin contributed one sitemap (`rooms`). Room categories, services and places had no sitemap entries. The rooms sitemap could also emit the homepage URL for a room with no slug, because the slug URL falls back to the homepage.

| Sitemap | Contents |
| --- | --- |
| `sitemap/rooms.xml` | rooms listing + published rooms with a slug (unchanged apart from the slug guard) |
| `sitemap/room-categories.xml` | published categories with at least one published room |
| `sitemap/services.xml` | published services |
| `sitemap/places.xml` | published places |

The new sitemaps are listed in `sitemap.xml` only when they contain content. URLs come from each model's own URL (`route()` / slug helpers), never a hard-coded host. They match the canonicals from batch 1. Checkout, booking tokens, customer pages, iCal feeds and unpublished items are not added.

Not included:

- **Foods — OWNER DECISION.** Food detail pages are menu items: a name, a price taken from the CMS, an image and optional text. Whether they are a real public offering with enough content to index is a business decision. The demo seed data includes food prices, so they also need fact-checking. Foods keep their canonical and noindex controls; add `'foods' => [Food::class, …]` to `AddSitemapListener::CONTENT` once approved.
- **Language alternates inside the sitemap.** Botble's sitemap view supports `xhtml:link` translations, but hreflang is now emitted in each page's HTML (batch 2). Google accepts either source, so the sitemap was left monolingual rather than duplicating the cluster logic.
- **Sitemap cache.** Botble caches sitemap output (`enable_cache_site_map`, default 60 minutes). After deploying, clear the cache or wait for it to expire before submitting in Search Console.

## Batch 4 — on-page foundations

- **Primary heading.** No public template rendered an `<h1>`. The page title in the breadcrumb banner (rooms, room, category, service, place, food, CMS pages, blog) was an `<h2>`. It is now the page's single `<h1>`. Every rule styling `.breadcrumb-title h2` also styles `h1` (SCSS source, compiled `theme.css`/`responsive.css` and their source copies under `platform/themes/riorelax/public`), so the banner looks unchanged. Customer-account pages already had their own `<h1>` and set no banner title, so they did not gain a second one.
- **Breadcrumb hierarchy.** Room and room-category pages now read Home › Rooms › item instead of Home › item. This adds a crawlable link back to the listing. The trail is also the source for BreadcrumbList in batch 5. The markup already used `<nav aria-label="breadcrumb">`, an ordered list and `aria-current`.
- **Metadata.** Batch 1 already applies per-item admin titles, descriptions and robots settings to every hotel type. Room categories have no description column, so their meta description is the admin SEO field or the site default. No copy was written to fill it.

Not changed (review required):

- **Homepage H1.** The homepage hides the banner, and its hero title (`simple-slider` / `hero-banner-with-booking-form`) is an `<h2>` styled by about a dozen theme and custom BW hero rules. Promoting it is a visual change to a design still being revised, and it should be done with the approved hero design. The slider title text is CMS content (`Enjoy A Luxury Experience` is Riorelax demo copy) and needs the owner's wording.
- **Pages with the banner switched off** (page meta `breadcrumb = No`) also have no `<h1>` unless their content provides one.
- The blog "no results" message in `views/templates/posts.blade.php` is an `<h1>`. On an empty blog list page it is a second `<h1>`. This was left as-is (cosmetic, no indexable value).

## Batch 5 — structured data foundation

Already present in Botble core and kept: **BreadcrumbList** (from the breadcrumb trail, which now includes the Rooms parent) and **WebSite** on every page, the blog Article schema and FAQ schema.

Added (`Botble\Hotel\Supports\HotelSchema`, wired in `HotelServiceProvider`):

| Page | Type | Properties |
| --- | --- | --- |
| homepage | `Hotel` | `@id` (site root `#hotel`, the same for every language), `name` (theme option `site_name`), `url` (localized homepage), `logo`/`image` (theme logo), `telephone`, `email`, `address` (as the free text the footer shows), `sameAs` (social links that point to a real profile path) |
| published room | `HotelRoom` | `@id`, `name`, `url`, `description` (tags stripped), `image` (the room's gallery), `containedInPlace` → hotel `@id` |

Safeguards:

- Values identical to the Riorelax demo seed (`info@webmail.com`, `14/A, Riorelax City, NYC`, `+908 987 877 09`) are dropped. So are bare platform URLs such as `https://www.facebook.com/`.
- If `site_name` is empty, no Hotel node is emitted.
- JSON is encoded with `JSON_HEX_TAG` because the theme writes inline scripts verbatim. A failure while building the schema is logged and never breaks the page.

Deliberately omitted until the owner verifies them: `starRating`, `geo`, `checkinTime`/`checkoutTime`, `priceRange`, `amenityFeature`, `aggregateRating`/`review`, `offers`, room `occupancy`/`bed`/`floorSize`, and a structured `PostalAddress`. The Riorelax seed text "Check-in time from 2 PM, check-out by 10 AM" is demo data and is not used. Room prices are not exposed as Offers, because booking happens on the external Aiosell engine.

Runtime check pending: validate the Hotel / HotelRoom JSON-LD with Schema.org's Schema Markup Validator after deployment, and use Google's Rich Results Test only for Google-supported rich-result types such as BreadcrumbList. Hotel / HotelRoom markup helps machine understanding but is not itself a Google Search rich-result type. Confirm that production `site_name`, `hotline`, `email`, `address` and `social_links` hold the hotel's real details and not demo values.

## Batch 6 — image and performance markup

- **Native lazy loading** on below-the-fold images: room cards (listing, related rooms, room-category grid) and their amenity icons, blog cards, gallery-detail photos, room thumbnails, and every room-gallery slide after the first. The first slide stays eager because it is the likely LCP image.
- **Masonry galleries index left eager.** `partials/gallery/galleries.blade.php` is laid out by Isotope after `imagesLoaded`, so lazy images would collapse the masonry grid.
- **Decorative images** (shortcode background and shape images) now have `alt=""` instead of announcing "Background image" or "Shape image". Content images already used data-driven alts (room, amenity, post, gallery, service and team names).
- The room amenity icon's invalid `width="20px"` is now `width="20"` (same rendering).

Not changed: `width`/`height` attributes on content images. Botble serves the original file when a size variant is missing, so declared dimensions could be wrong. They need a media audit or CSS `aspect-ratio` work done with the design. No source media was modified. Generic alts such as `__('Image')` on CMS-chosen shortcode images were kept, because the right text depends on the image content the owner uploads.

## Batch 7 — measurement preparation (no code change)

Existing support: Botble renders Google Tag Manager, a GA4 measurement ID or custom tracking code from **Admin → Settings → Website tracking** (`ThemeSupport::renderGoogleTagManagerScript`, printed in the theme header). No IDs exist in the repository, and none were added.

Every booking handoff to Aiosell is a plain link to the theme option `external_booking_url` (`https://be.aiosell.com/book/acc8e772e0` in `BookingEngineSettingsSeeder`), opened in a new tab:

| Placement | Template | Distinguishing class |
| --- | --- | --- |
| header button | `partials/header.blade.php` (`header_button_url`) | `top-btn` |
| room card / related rooms | `partials/rooms/item.blade.php` | `book-button-custom` |
| room-category grid | `views/hotel/room-category.blade.php` | `book-button-custom` |
| booking form (hero pill, sidebar) | `partials/hotel/forms/form.blade.php` | `round-search-btn` / `ss-btn` |
| booking-form shortcode | `partials/shortcodes/booking-form/index.blade.php` | `ss-btn` |

GTM's built-in link-click trigger can measure these without theme code: **Just Links**, Click URL Hostname equals `be.aiosell.com`, sent as a GA4 event such as `booking_engine_click` with `link_classes`, `page_location` and the page `lang`. A custom script was therefore not added. Record this event as **intent to book**, never as a booking or a conversion value.

Pending external access (not implementable in code here):

- **Completed bookings** happen on `be.aiosell.com`. Attributing them needs Aiosell to support a GA4/GTM tag or a confirmation-page redirect/postback, plus GA4 cross-domain configuration (Admin → Data streams → Configure your domains) that includes `be.aiosell.com`. This requires Aiosell account/support access.
- GTM/GA4 container IDs, Search Console verification and sitemap submission, and Google Business Profile linkage require the owner's Google accounts.

Observed but out of scope (booking handoff, protected): when `external_booking_url` is set, the date/guest search form links to the engine without passing the selected dates or guests. Guests must re-enter them on Aiosell. Whether Aiosell accepts deep-link parameters must be confirmed with the provider before the handoff is changed.

## Verification summary (end of session)

| Check | Result |
| --- | --- |
| `php tests/Seo/run.php` | 29 / 29 PASS |
| `php tests/Security/run.php` (security + booking/backend regressions) | 49 / 49 PASS (same as baseline) |
| `vendor/bin/phpunit --testsuite Unit` | OK (1 test) |
| `vendor/bin/phpunit --testsuite Feature` | ERROR: `MissingAppKeyException`. No local `.env`/app key or installed database; unrelated to these changes and not worked around |
| `php -l` on every changed PHP file; Blade compile + `php -l` on all 13 changed templates | no errors |

Not verified: rendered HTML on a running site, Schema Markup Validator / applicable Google Rich Results checks / URL Inspection, and real production language and theme-option values.
