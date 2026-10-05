# Technical SEO audit and local repairs — 5 October 2026

The frontend remains a separate design preview. No frontend/backend integration or deployment is included. English, Arabic and draft Simplified Chinese are retained.

## Findings and repairs

* All 30 original shells shared the same English title/description and `noindex,nofollow`. Non-home shells initially had empty main content. Keep preview exclusion; provide explicit launch configuration rather than silently indexing unapproved content.
* Add localized titles/descriptions, canonical URLs without tracking parameters, Open Graph/Twitter metadata, reciprocal EN/AR/zh-Hans/x-default alternates, a minimal Hotel entity and breadcrumbs. No invented rating, price, availability, coordinates or commercial policy is added to structured data.
* Generate sitemap entries only for public routes and their three language variants. Exclude booking, account, password and design review routes. Preview robots disallows crawling and preview sitemap is empty.
* Prerender all three language versions so raw HTTP contains the localized content and metadata. The preview server selects snapshots by `?lang=`. Apache rewrite rules are emitted for equivalent static hosting; these require staging validation on the actual server and must not replace the Laravel root configuration.
* Preserve actual 404 responses and redirect directory URLs and explicit index.html duplicates to the trailing-slash route. Use the correct XML content type. Do not return 304 for an error page.
* Refresh metadata after internal navigation, including cached views. Keep language variants separate in the service worker page cache; the original pathname-only cache could serve another language offline.

## Local commands

`npm run build`, then `npm run prerender`, then `npm run preview` and `npm run test:seo`. Prerender requires Playwright Chromium, or set `HOTEL_BROWSER_PATH` to a Chromium executable. The renderer only fetches loopback pages/assets. Build uses the pinned Sharp dependency; it does not submit content to a translation or rendering service.

Preview is the default. For a future approved release, set `HOTEL_SITE_ORIGIN` to the approved bare HTTPS origin and `HOTEL_INDEXABLE=1`, then rebuild and prerender. Both commands are required; do not publish an unrendered indexable build. Language queries must select the corresponding HTML on the deployed server. Never publish the reserved `.invalid` origin used by local tests.

## Decisions and remaining verification

Hotel approval of Chinese/editorial content, policies and model illustrations is still required before enabling indexing. The final domain and redirect ownership must be confirmed; no live redirect or robots change was made. Sitemap submission, Search Console checks, provider integration, field Core Web Vitals and Apache/Hostinger rewrite validation require the future staging/release environment. The standalone worker must stay away from authenticated CMS, payment and admin pages during integration.

The existing 3D demand rendering, singleton model/renderer, local fonts, WebP variants and content hashes were preserved. Local baseline reproduced 52% base photography reduction, 101 draw calls and the existing 16 browser checks plus geometric checks.
