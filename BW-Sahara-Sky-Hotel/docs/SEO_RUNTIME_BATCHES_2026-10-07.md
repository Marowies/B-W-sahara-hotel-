# SEO runtime follow-up — 2026-10-07

The local SQLite database is empty QA infrastructure, not a copy of the hotel website. No seeders, hotel facts or language records are inserted. Existing SEO batches were inspected before changing code.

## Batch 1 — technical SEO

- Replaced the static WordPress robots file with a Laravel plain-text route. Sitemap comes from `route('public.sitemap')`, never a fixed production host.
- Botble robots editor and uploads now use `storage/app/robots.txt`. Custom crawl rules are preserved; sitemap directives are normalized to the installation's route. Default rules allow crawling, including utility pages so crawlers can see their noindex.
- Homepage canonical/Open Graph URL use the localized `public.index` route. The no-page fallback respects existing CMS SEO title, description and indexability settings. No hotel text is generated when settings are absent.
- Existing room/detail canonicals, query stripping and utility/private noindex already exist and remain unchanged.
- SEO regression suite: 32/32 pass; security/backend regression suite: 49/49 pass. Runtime GETs: robots 200 text/plain with local sitemap, homepage 200 with canonical, rooms query 200 canonical without query, login 200 noindex. PHP syntax and diff checks pass.

Release consideration (no deployment performed): remove the previous static `public/robots.txt` from any future deployed web root, otherwise the web server will bypass the dynamic route. Preserve any owner-approved custom production rules in the new storage file during a separately authorized release. Storage must be writable for the existing editor. No production rules were read or modified here.

Full real-content and AR/ZH runtime validation remains blocked by absent local CMS content/languages. PHPUnit's existing bootstrap readability blocker is separate from the passing standalone suites.

## Batch 2 — international SEO

Inspected LanguageManager locale selection and Language Advanced translated-slug resolution. Locale URL keys derive from stored lang_locale (or lang_code on collision); hreflang derives from lang_code. No zh/zh_CN assumption or language record was added. With zero local language rows Botble supplies its own English fallback.

Fixed x-default to reuse the resolved default-language URL in the alternate cluster, including translated slugs. Generic language aliases now keep the first configured regional target across every page rather than changing with the active region. Added rendered-Blade and multi-region reciprocity regressions; 34/34 SEO checks pass. Real EN/AR/ZH equivalence/RTL and translated content remain pending a sanitized CMS dataset.

Push blocker: normal Git credential helper cannot launch its shell in this environment; invoking the installed manager directly returns credential enumeration Access is denied. Commits remain local until GitHub authentication is available. No credential was displayed or changed.

## Batch 3 — sitemap and crawl discovery

Published hotel records explicitly marked noindex in the existing SEO meta box are now excluded from room/category/service/place sitemaps. Metadata is eager-loaded to avoid per-item metadata queries. A temporary in-memory fixture tests all four content models and the absence-of-metadata fallback; the local QA SQLite remains empty. Corrected prior documentation and recorder URLs to the actual /rooms.xml, /room-categories.xml, /services.xml and /places.xml routes. SEO suite 35/35 passes. Foods inclusion and translated content discovery remain owner/content decisions; no speculative URLs were added. Push remains pending the credential blocker documented above.

## Batch 4 — on-page foundations

Existing H1, breadcrumb hierarchy and descriptive image alt foundations were already implemented. Configured CMS homepage metadata now falls back to that page's stored name/description only after explicit SEO title/description and site title options are exhausted. Raw shortcode content is not used for descriptions. Added a regression contract; SEO suite 36/36 passes and PHP lint/diff checks pass. Missing real hotel copy and untranslated fields remain pending; the empty default homepage was not populated. Push pending authentication.

## Batch 5 — verified structured data foundations

Existing Hotel/HotelRoom schema already omits unverified ratings, offers, prices, amenities, coordinates and check-in/out times. Added the exact documented demo site title to the exclusion list; schema image/logo values must be absolute HTTP(S) URLs and room images remain deduplicated. JSON-LD serialization tolerates malformed CMS UTF-8 while retaining script-tag escaping. Added two behavior regressions; SEO suite 38/38 passes. Dynamic CMS data is still subject to the facts reconciliation register; no owner facts were manufactured and no local Hotel node was forced. Push pending authentication.

## Batch 6 — safe image/performance SEO

The first room-gallery image now receives fetchpriority=high and all main carousel images use decoding=async. Later slides retain lazy loading; only one slide can be high priority. The rendered-Blade regression checks these attributes. No dimensions were guessed and no booking/pricing markup or behavior changed. SEO suite 38/38 and isolated security/backend suite 49/49 pass. No Core Web Vitals improvement is claimed without real media/runtime measurements. Push pending authentication.

## Final scope and external dependencies

No production access, deployment, seeding, composer update, booking/payment/inventory/pricing/security business-logic change, merge or force push. Work remains on seo/strategy-and-implementation. Existing main/backend refs were not moved; no frondend/new-frontend branch was created or edited. .env/SQLite/storage files are not committed.

Owner/CMS: sanitized real pages/rooms/translations, actual EN/AR/ZH language codes and prefixes, verified hotel identity/contact/logo/profile links, real image dimensions, content equivalence, foods indexing decision. GSC: sitemap submission, rendered URL inspection, coverage and language diagnostics. GA4/GTM: existing property/container IDs, authorized access and event/conversion verification. GBP: verified profile ownership and identity reconciliation. Aiosell: provider-confirmed booking deep-link parameters, cross-domain conversion attribution and any price/availability data contract. No external system was configured here.

Push is blocked by local GitHub credential access, not by a merge conflict. Every batch has a separate local commit. Do not describe these commits as uploaded until remote verification succeeds.
