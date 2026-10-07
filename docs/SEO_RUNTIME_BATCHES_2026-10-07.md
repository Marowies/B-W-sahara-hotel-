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
