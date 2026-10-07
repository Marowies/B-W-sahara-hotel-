# Multilingual URL architecture

Implemented locally on 2026-10-07. English is unprefixed; Arabic and Simplified Chinese have separate, stable URLs. This is locale routing, not Generative Engine Optimization.

## Project variables

| Variable | Hotel implementation |
| --- | --- |
| Product | B&W Sahara Sky Hotel |
| Canonical origin | https://bwsaharaskyhotel.com |
| Default / target locales | en / ar, zh; hreflang zh-Hans |
| RTL | Arabic; html lang/ar and dir/rtl |
| Markets | International English-speaking travellers, Arabic-speaking visitors, Chinese-speaking visitors. Commercial priorities are proposed, not validated traffic statistics. |
| Stack | Existing static templates + browser runtime + mandatory Playwright HTML prerender; existing Laravel/Botble local API |
| Hosting | Existing Vercel configuration; portable Node preview. Production hotel hosting must be confirmed before deployment. |
| Country header | x-vercel-ip-country, trusted only inside Vercel's platform proxy |
| Currency | Existing server-owned room currency, currently USD; language does not convert prices or change payment configuration. |
| Billing | Existing Pay@Hotel external-engine evidence; no newly enabled gateway, checkout, or automatic payment-link claim. |
| Analytics | Privacy-safe hotel:analytics events + optional hotelAnalyticsAdapter; no external tracking SDK/account assumed. |
| Locale preference | hotel_locale cookie; authenticated customers may save ht_customers.ui_locale through a CSRF-protected API. |
| Legal | Current privacy/terms are draft and noindex. English can govern only after hotel approval of authoritative English text; native legal translations still need legal review. |

## Single sources

- `src/shared/locale.mjs`: logical English paths, native public paths, locale definitions, indexing status.
- `src/shared/locale-resolver.mjs`: legacy redirects, cookie override, server-country allowlist and crawler exclusions.
- `src/messages/{en,ar,zh}.json`: marketing/app/auth/emails/errors/home/seo/geo namespaces. Email namespace is prepared; this change does not translate existing CMS transactional emails.
- `src/shared/metadata.mjs`: metadata, reciprocal hreflang, schema and page-content selection, shared between tests and browser/prerender.
- `src/content/entity.mjs`: confirmed hotel identity and official profiles.

Source templates stay on English logical paths. Generated language files exist only in `dist`; there are no duplicated authored page trees and no generated `/localized` legacy tree. `?lang=` is accepted only to return a permanent redirect, never to serve different languages on one canonical URL. Internal links and the language switcher use the native pathname helper.

## Redirect policy

GET/HEAD only. Existing locale prefixes, unknown routes, admin/API/webhook/asset paths and non-indexable account/preview pages bypass country selection. Known crawlers bypass preference/country selection. Explicit legacy URL migration still applies to crawlers so old URLs converge to the requested canonical locale.

Valid `hotel_locale=en|ar|zh` preferences override the country mapping. EN prevents automatic Arabic selection in Egypt. Existing prefixed URLs preserve their language even when the cookie differs. Country mapping: EG/SA/AE/QA/KW/BH/OM/JO/LB/IQ/MA/DZ/TN → ar; CN → zh; otherwise en. All automatic preference/country redirects are 307 with private/no-store caching and Vary Cookie/User-Agent. Canonical and legacy normalization use 301.

Local Node preview deliberately ignores supplied country headers. Query parameters and client-submitted country/currency never control country routing. `proxy.js` enables the trusted platform header only when `VERCEL=1`; that deployed behavior still needs a real Vercel acceptance check. Do not expose a general origin accepting forged platform headers.

The cookie is SameSite=Lax, Secure on HTTPS, lasts one year and contains only an enumerated locale. The database endpoint writes only the authenticated customer, never a client-supplied account ID. Guests receive 401; malformed locales receive 422; missing CSRF receives 419. Session response exposes saved ui_locale for authenticated clients. Current static account screens are not a completed customer-auth integration.

Connected room lists use the existing localized catalogue names for Arabic/Chinese and server-owned numeric availability/price fields. English-only backend descriptions do not overwrite localized room-detail content. A new CMS room without authored localized copy uses a neutral localized room label and contact link until translations are written. Backend error messages are also mapped to a localized summary on Arabic/Chinese pages.

## Content intent and localized commercial phrases

| Page family | Job | English / Arabic / Chinese phrases |
| --- | --- | --- |
| Home | Identify hotel and invite an enquiry | desert hotel / فندق في الصحراء / 沙漠酒店 |
| Rooms | Compare confirmed room categories | hotel rooms / غرف الفندق / 酒店客房 |
| Individual room | Describe one category/occupancy | Standard, Superior, Deluxe / قياسية، مميزة، ديلوكس / 标准、高级、豪华客房 |
| Experiences | Explain what to confirm before activities | desert experiences / تجارب الصحراء / 沙漠体验 |
| Services | Clarify requests and inclusions | hotel services / خدمات الفندق / 酒店服务 |
| Contact | Direct official contact and directions | contact the hotel / تواصل مع الفندق / 联系酒店 |
| Guide | Informational planning, not another room listing | plan a desert stay / تخطيط إقامة صحراوية / 沙漠住宿规划 |

The guide, category listing and individual rooms have different jobs. No country-specific English doorway pages were added. Content proposals avoid unsupported booking guarantees, travel times, meal inclusions, facility claims or invented competitors. Arabic is conversational for enquiries; Chinese emphasizes advance confirmation, itinerary and practical planning. Native editorial review remains appropriate before publication.

## Build and crawl safety

`npm run build` requires both image/assets build and HTML prerender; failure in either fails the build. Vercel uses serverless Chromium when necessary and has no client-only fallback. Local builds and Vercel previews are noindex and robots Disallow /. `npm run release` or Vercel production creates the 48 verified canonical sitemap URLs with reciprocal alternates. Never run the release command against the publicly reachable preview unintentionally.

There are 31 logical routes and 93 rendered language pages, but only 16 page families / 48 URLs are indexable. Draft blog/fictional stories, unconfirmed dome offering, legal drafts, booking/account/review/confirmation/design review and error pages are excluded from the public sitemap and schemas. Private API/admin pages are also blocked in robots; actual authorization remains the protection for private data.

Dates are the material editorial creation/review date 2026-10-07, not a timestamp bumped on every build. Content changes should update reviewed dates only after a real revision. Sitemap entries omit lastmod where no reviewed content exists.

## Add a locale or page

1. Add locale to languages/RTL settings and a distinct native path to every registry row; update resolver only for confirmed markets.
2. Author the full message catalogue and localized SEO/GEO content, preserving keys/placeholders. Update script locale labels, font choices and native editorial review.
3. Add an English source template only for a new logical page, plus registry/message entries. Mark unconfirmed/draft offers non-indexable.
4. Run build, `npm run test:seo`, browser/mobile checks and the existing 3D regression. Canonicals/hreflang/sitemap use the registry automatically.
5. Add new authoritative page IDs to the llms generator's curated list if appropriate. Never fabricate a price, policy, review or author just to fill a page.

Authoritative reference: [Google localized versions](https://developers.google.com/search/docs/specialty/international/localized-versions), [Vercel routing middleware/proxy](https://vercel.com/docs/routing-middleware).

## Complete slug map

<!-- Generated registry table follows. -->

| Page ID | English | Arabic | Chinese | Indexable |
| --- | --- | --- | --- | --- |
| home | / | /ar/ | /zh/ | Yes |
| rooms | /rooms/ | /ar/ghoraf/ | /zh/kefang/ | Yes |
| about | /about-us/ | /ar/aan-al-fondoq/ | /zh/guanyu-jiudian/ | Yes |
| services | /services/ | /ar/khedmat/ | /zh/fuwu/ | Yes |
| experiences | /experiences/ | /ar/tajarob-al-sahraa/ | /zh/shamo-tiyan/ | Yes |
| gallery | /galleries/ | /ar/sowar/ | /zh/xiangce/ | Yes |
| blog | /blog/ | /ar/yawmeyat/ | /zh/shamo-rizhi/ | No |
| contact | /contact-us/ | /ar/tawasul/ | /zh/lianxi/ | Yes |
| faq | /faq/ | /ar/asela-shaea/ | /zh/changjian-wenti/ | Yes |
| privacy | /privacy/ | /ar/khososiya/ | /zh/yinsi-zhengce/ | No |
| terms | /term-and-conditions/ | /ar/shorout/ | /zh/tiaokuan/ | No |
| guide | /guides/planning-a-desert-stay/ | /ar/dalil/takhteet-eqama-sahraweya/ | /zh/zhinan/shamo-zhusu-jihua/ | Yes |
| room-deluxe-single | /rooms/bw-sahara-sky-hotel-deluxe-room-single/ | /ar/ghoraf/deluxe-fardeya/ | /zh/kefang/haohua-danren/ | Yes |
| room-deluxe-double | /rooms/bw-sahara-sky-hotel-deluxe-room-double/ | /ar/ghoraf/deluxe-mozdawaga/ | /zh/kefang/haohua-shuangren/ | Yes |
| room-standard-single | /rooms/standard-room-single/ | /ar/ghoraf/qeyaseya-fardeya/ | /zh/kefang/biaozhun-danren/ | Yes |
| room-standard-double | /rooms/bw-sahara-sky-hotel-standard-room-double/ | /ar/ghoraf/qeyaseya-mozdawaga/ | /zh/kefang/biaozhun-shuangren/ | Yes |
| room-standard-triple | /rooms/bw-sahara-sky-hotel-standard-room-triple/ | /ar/ghoraf/qeyaseya-tholatheyya/ | /zh/kefang/biaozhun-sanren/ | Yes |
| room-superior-single | /rooms/bw-sahara-sky-hotel-superior-room-single/ | /ar/ghoraf/momayaza-fardeya/ | /zh/kefang/gaoji-danren/ | Yes |
| room-superior-double | /rooms/bw-sahara-sky-hotel-superior-room-double/ | /ar/ghoraf/momayaza-mozdawaga/ | /zh/kefang/gaoji-shuangren/ | Yes |
| domes | /rooms/copper-glass-domes/ | /ar/ghoraf/qebab-zogageya/ | /zh/kefang/boli-qiongding/ | No |
| story-slow | /blog/the-art-of-a-slower-stay/ | /ar/yawmeyat/eqama-ala-mahlak/ | /zh/shamo-rizhi/man-jiezou/ | No |
| story-stars | /blog/a-night-beneath-the-stars/ | /ar/yawmeyat/leila-taht-al-nojoom/ | /zh/shamo-rizhi/xingkong-zhi-ye/ | No |
| story-desert | /blog/a-desert-made-for-discovery/ | /ar/yawmeyat/sahraa-lel-ektshaf/ | /zh/shamo-rizhi/tansuo-shamo/ | No |
| booking | /booking/ | /ar/takhteet-eqama/ | /zh/zhusu-jihua/ | No |
| review | /booking/review/ | /ar/takhteet-eqama/moragaa/ | /zh/zhusu-jihua/queren/ | No |
| confirmation | /booking/confirmation/ | /ar/takhteet-eqama/moayena/ | /zh/zhusu-jihua/yulan/ | No |
| login | /login/ | /ar/dokhool/ | /zh/denglu/ | No |
| register | /register/ | /ar/hesab-gadeed/ | /zh/zhuce/ | No |
| forgot | /forgot-password/ | /ar/esteeadet-kalemet-moroor/ | /zh/zhaohui-mima/ | No |
| approval | /design-review/ | /ar/moragaat-tasmeem/ | /zh/sheji-shenhe/ | No |
| 404 | /404.html | /ar/404.html | /zh/404.html | No |
