# SEO + GEO implementation and acceptance

Implemented in the existing `new-frontend` working checkout and the local `backend` checkout. No GitHub push, production deployment, payment integration, third-party posting or production database migration was performed.

## Changes

| Area | Implementation |
| --- | --- |
| URLs | One registry for 31 logical pages / 93 EN, AR, ZH URLs; native commercial slugs; no query-language content serving or duplicated authored locale trees |
| Routing | Shared permanent legacy/canonical redirects, trusted-platform human-country 307, crawler bypass and explicit locale cookie override |
| Translation | Central catalogues, key parity, Arabic RTL, language switcher using equivalent native paths |
| SEO | One metadata helper for self-canonical, reciprocal hreflang/x-default, sitemap, robots and relevant schema |
| Crawl content | Mandatory full HTML prerender with build failure on errors; no empty/client-only production fallback |
| GEO | Verified Hotel identity, answer-first blocks, visible FAQ/schema pairs, own-category Best suited to / Not suitable for comparison, planning guide, llms.txt and markdown mirrors |
| Backend | CSRF-protected authenticated customer locale preference endpoint and nullable ui_locale migration, applied only to the isolated local clone |
| Analytics | Anonymous event adapter carrying page ID, locale and traffic channel; separate observed AI referrals and no personal query/referrer data |
| Documentation | LOCALES.md, GEO.md, owner checklist, honest discovery notes, weekly questions, CSV and Excel citation ledger |

Key authored files: `src/shared/locale*.mjs`, `src/shared/metadata.mjs`, `src/content/entity.mjs`, `src/messages/*.json`, `src/js/{i18n,geo,analytics,seo}.js`, existing approval/runtime scripts, guide template and performance CSS. Build/routing changes are in `tools/{build,compile,prerender,seo,serve,vercel-build}.mjs`, `proxy.js`, `vercel.json` and pinned package manifests. Some modified 3D/logo/admin files predate this SEO task and are preserved.

## Validation

Automated result snapshots are in `VALIDATION_2026-10-07.json` and `test-results/seo`. Coverage includes all 93 raw HTML language pages, public schema/FAQ visibility, 48 production sitemap URLs, noindex draft/account pages, crawler-safe country rules, local country-header spoof rejection, language-cookie behavior, analytics navigation/PII checks and mobile AR/ZH layouts.

Existing regression results: 16 frontend checks passed, including lazy 3D loading, reuse of one model/renderer/canvas across navigation, photographic fallback, reduced motion and asset caching. Model geometry/layout verification passed. Twelve local backend/admin integration groups passed, with seven published rooms, contact inbox save, real admin login and denial for a restricted staff account. Four live availability checks passed. The marked contact-test record was removed after verification.

Customer DB preference test persisted all three permitted locales, rejected invalid input and rolled back its synthetic customer. Actual HTTP checks verified CSRF 419 and guest 401. Vercel proxy handler smoke verified Egyptian human redirect and GPTBot bypass, but actual deployed routing remains untested.

Initial local desktop observation before scrolling: LCP about 1.05 seconds and CLS about 0.00035; later lifetime observations include model controls and client navigation. These are synthetic Chrome/SwiftShader observations, not field Core Web Vitals, PageSpeed certification or a physical-phone benchmark.

Excel summary formulas were exercised with temporary 3-run cases (2 answers, 1 brand mention), verified against expected rates, then restored to pending/Not measured. Both sheets were rendered and checked. Its 144 rows are proposed tests, not observed citations.

## Remaining owner / external acceptance

- Approve hotel operating facts, primary contact and legal content; obtain native editorial review. Unconfirmed offers and legal/blog drafts remain noindex.
- Confirm deployment host and backend routing before deployment. Native language files are ready; Vercel trusted-country behavior still needs real deployment acceptance. The local preview remains noindex.
- Apply the customer-locale migration on the intended backend before publishing the connected endpoint. Production DB has not been changed.
- Connect the chosen analytics adapter/account if wanted. Existing CMS transactional emails and customer authentication screens are not fully integrated by this SEO change.
- After an approved production release, submit sitemap and inspect pages in Google Search Console/Bing Webmaster Tools using property-owner access.
- Measure three fresh-engine discovery prompts and complete the citation baseline, then repeat weekly. Current local content cannot be fetched by public engines. No actual AI citation rate, search submission, or promised ranking is claimed.

How to extend languages/pages is documented in LOCALES.md; content/entity/measurement workflow is in GEO.md. Public references and factual boundaries are linked there.
