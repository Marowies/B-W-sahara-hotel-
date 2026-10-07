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
