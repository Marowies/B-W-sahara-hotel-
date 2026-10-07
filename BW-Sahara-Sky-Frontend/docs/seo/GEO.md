# Generative Engine Optimization for B&W Sahara Sky Hotel

Generative Engine Optimization (GEO) is structuring your content and off-site authority so AI answer engines cite B&W Sahara Sky Hotel inside the answers they generate. Where SEO optimizes for ranked links, GEO optimizes for cited passages. Country-to-language redirects are locale routing, not GEO.

## Identity and verified boundaries

Frozen English one-liner, reused on home/About, Hotel schema and llms.txt:

> B&W Sahara Sky Hotel is a desert hotel between Bahariya Oasis and Farafra in Egypt, for travellers planning rooms and desert experiences.

Arabic and Chinese reuse their localized identity sentence. Category terms: desert hotel, hotel between Bahariya Oasis and Farafra, desert accommodation, hotel rooms and desert experiences. No competitor comparison is authorized or evidenced; the rooms table compares the hotel's own categories instead.

Confirmed inputs: the real domain and three official social URLs supplied by the owner, seven published local room records, phone and Gmail contact in the supplied booking confirmation. Location is the Bahariya–Farafra road. Exact geographic coordinates, founding year/story, named founder, star rating, certifications, fixed rates, transfer durations, dome availability and activity inclusions remain unverified. No fake Person, Review, AggregateRating or Offer schema was added. The planning guide is attributed to the hotel's website team, an Organization, rather than an invented expert.

The Hotel entity is already an Organization subtype. Hotel/WebSite/WebPage/BreadcrumbList/FAQPage are used where relevant; Article is used for the new planning guide. SoftwareApplication is inappropriate for this hotel. The actual payment example is confirmed with Pay@Hotel, not proof of captured payment; automatic bank/Aiosell payment-link delivery remains unverified. Nothing here enables a gateway or modifies money-handling code.

## Citation Trinity

1. Identity: one name, origin, contact details, entity schema and real sameAs profiles.
2. Extractability: direct answer near the top, supporting facts, category table, visible FAQ questions/answers in raw HTML and matching FAQPage schema.
3. Corroboration: independent coverage and consistent real profiles. Owned pages alone do not prove independent authority.

Page formula: a buyer-question H2, a direct self-contained answer, strongest verified supporting fact, practical limits, then a relevant enquiry/guide link. Keep unsupported claims out. The short fixed identity sentence is intentionally concise; longer planning answers provide specifics. Don't pad paragraphs merely to meet a word quota.

Implemented on home, About, rooms + individual room pages, services, experiences, gallery, contact, FAQ and the planning guide. Room comparison explains which category to enquire about and what still needs confirmation. Dome visualization is clearly illustrative; the dome offer page is a noindex draft. No thin competitor/price/certification page was created from missing evidence.

## Technical substrate

- Mandatory prerender provides headings, answer blocks, tables, FAQ text, schema, canonical and hreflang without client JavaScript.
- Production robots permits public pages for all crawlers, including AI search crawlers, while excluding private/draft paths. Preview builds intentionally block all crawlers.
- llms.txt is generated from the registry and curated authoritative page IDs, with multilingual HTML and markdown links. Markdown mirrors include the same core answers/FAQs and material review date; they do not mirror live availability or private data.
- AI-referral classification separates known AI referrers/UTM codes from organic/referral/direct events. It sends page ID, locale and channel only. Attach an approved analytics adapter if needed; no external analytics account, personal identifiers, full referrers or query values are sent by default. Referrers can be absent, so this is observed referral attribution, not complete citation measurement.

[Google's AI-features guidance](https://developers.google.com/search/docs/appearance/ai-features) does not require special AI markup and does not guarantee inclusion. [llms.txt](https://llmstxt.org/) is an optional proposed agent map, not a replacement for crawl access, indexability or evidence. FAQ schema is not a promise of a hotel FAQ rich result. Schema vocabulary: [Hotel](https://schema.org/Hotel).

## Weekly measurement

Target engines: ChatGPT, Claude, Perplexity, Gemini, Copilot, Google AI Overviews. `WEEKLY_PROMPTS.json` contains eight proposed questions in each of three languages; `CITATION_LEDGER.csv` contains 144 engine × locale × prompt seed rows. The accompanying Excel ledger includes a four-week date filter and verified present-rate/cited-rate formulas.

1. Start a fresh chat/search with no previously supplied hotel context. Use the exact seed question and record engine, mode, locale and date.
2. Record whether the answer/container appeared, hotel was named, a link appeared, cited URL/passage, competing hotels named and an answer/screenshot evidence link. Use Yes/No only after observing the result.
3. Mark Complete only after recording evidence and Yes/No outcomes. Excel's Ready? column is calculated from date, all three outcomes, evidence link and completion status; incomplete records are excluded. Sign-in failures, captchas and outages stay Pending, not a measured No. Not measured is distinct from a measured 0%.
4. Present-rate = runs with an answer / completed runs. Cited-rate = brand-named runs where an answer appeared / runs with an answer. A brand mention and an actual linked citation are separate fields.
5. Review by engine, language and prompt. Do not mix branded lookup questions with unbranded discovery questions when judging discovery effectiveness. The overview is an aggregate workflow count, not a controlled comparative benchmark.
6. Re-run in the same mode after improving the corresponding answer/FAQ/table or earning a credible third-party mention. Extend the table and bounded formulas when adding another week's rows.

No independent engine citation baseline is recorded yet. Three live web-discovery query strings were checked on 2026-10-07; see SPOT_CHECKS.md. Those results are not AI answers and are excluded from the citation rates. New pages are local and cannot influence public answer engines until a reviewed production release is deployed and crawled. Browser engines/accounts and Search Console/Bing property access were not supplied; do not mark these steps complete without evidence.

## Engine notes and publication handoff

Use the same accessible public site for all engines. Check Google indexing for Google Search/AI surfaces and Bing indexing for Bing/Copilot discovery. For each service, verify its current crawler/access and search-mode behavior rather than assuming all answers use the same retrieval system. Training availability and live search retrieval are different pathways; allowing a training bot alone is not a citation strategy.

After approved deployment: verify root canonical redirects, inspect representative English/Arabic/Chinese URLs in Google Search Console and Bing Webmaster Tools, submit the generated sitemap, and run the three initial unbranded discovery questions in fresh engine sessions. Verification tokens and account actions are still owner-dependent. IndexNow is optional if publication becomes frequent; no API key or invented submission was created.

Review room/category facts monthly, guides quarterly, and contact/payment claims immediately after operational changes. Change the visible review date only after material review. Regenerate llms/sitemap on every build; never buy fake reviews or spam citation links.

See OFFSITE_CHECKLIST.md for the owner/founder work that requires real accounts and independent corroboration. Nothing has been posted or messaged to third parties.
