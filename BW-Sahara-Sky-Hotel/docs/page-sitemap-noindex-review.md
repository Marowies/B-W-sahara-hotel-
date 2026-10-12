# Page sitemap noindex handling and alternate-page review

The Page sitemap listener now skips published Pages whose CMS seo_meta[index] is noindex. Published index/default Pages remain included; draft and pending Pages remain excluded by wherePublished(). Metadata is eager-loaded. No slug blacklist or content change is introduced.

The database-backed SEO regression invokes the production Page listener with published index, published noindex, published without metadata, draft and pending fixtures, and verifies the listener does not handle other sitemap keys. The standalone harness explicitly selects current Page source rather than an optimized dependency classmap.

## Four existing pages (read-only review, 12 October 2026)

| Path | Usage evidence | Later unpublishing assessment |
|---|---|---|
| /home-page-02 | Seeded alternate homepage; live London/placeholder content. No incoming link in ten sampled public pages; currently in pages.xml. | Candidate, conditional on actual homepage_id, menu and translation settings. |
| /home-page-full-menu | Seeded alternate full-menu layout; live page links to itself. No other incoming link in the sample; currently in pages.xml. | Candidate, conditional; ensure this record is not selected as homepage and that any custom menu reference is removed. |
| /home-page-side-menu | Seeded alternate side-menu layout; live placeholder content. No incoming link in sample; currently in pages.xml. | Candidate, conditional on actual homepage/menu/translation settings. |
| /team | Live staff names match TeamSeeder. MenuSeeder contains a /team item; PageSeeder uses the teams shortcode. No incoming link found in sampled current public pages. | Not safe to declare unconditional: verify and remove active menu references, and confirm it is not an intended real staff page. |

ThemeOptionSeeder sets homepage_id=1, the seeded Home Page 01, but this is not evidence of the current database value. Alternate page records use the shared theme/layout/shortcodes; no runtime dependency on these exact three slugs or /team was found in theme source. Unpublishing a Page does not delete the shared theme layouts or Team records. A teams section can remain on other Pages (PageSeeder includes one on FAQ); that is a separate content decision.

No live CMS database/menu export or authenticated settings was available in this read-only review. Public-link checks covered these seven discovered URLs plus /, /rooms and /about-us, not a complete site crawl. Sitemap membership provides discovery even where navigation references are absent. No page is automatically noindexed or unpublished by this patch.

## Staging acceptance

Set one disposable published Page to noindex: confirm noindex HTML and absence from pages.xml after sitemap cache regeneration. Restore index: confirm inclusion. Confirm real room and hotel Pages remain present. Before any later unpublishing, verify homepage_id for each locale, menu record references and custom URLs, and inbound links; confirm the remaining homepage/layout/booking navigation works. No production changes are authorized by this document.
