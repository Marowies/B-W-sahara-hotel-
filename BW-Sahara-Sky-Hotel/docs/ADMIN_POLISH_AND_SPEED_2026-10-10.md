# Admin usability and local loading performance

## Corrected defects

- Dashboard posts and activity text now wrap within their cards. These widgets
  do not require horizontal scrolling on phones.
- Cache suggestion actions wrap and maintain adequate touch targets. These
  notices are configuration suggestions, not application errors. The change
  does not silently enable shortcode/widget caching.
- Previous-page pagination no longer incorrectly declares itself disabled
  and is keyboard accessible. Pagination controls have a visible boundary.
- Responsive table details wrap long room names and values. The detail toggle
  is visible and separated from translation controls. Translation controls
  have larger targets and accessible language labels.
- Payment settings cards use a bounded mobile layout. Provider descriptions,
  COD and bank transfer actions stay inside their cards. No gateway settings
  or statuses were changed.
- Media height and footer spacing are bounded on phones. Missing previews use
  a labeled placeholder instead of broken-image text. Two existing referenced
  originals, `backgrounds/an-img-05.png` and `backgrounds/testimonial-bg.png`,
  were recovered from identical repository source paths. `footer-bg0.png`
  has no matching source file found; its preview remains unavailable.

## Performance changes

- Branded admin uses its existing self-hosted fonts. Rendering its header no
  longer calls the synchronous external Google font downloader.
- Local gateway serves public vendor/storage assets directly, with gzip,
  ETags, 304 handling and one-hour browser caching. Its memory cache is bounded
  to 32 MiB/128 entries and invalidates changed files. Large files fall back
  to the backend. Missing recognized assets return an immediate 404 instead
  of bootstrapping Laravel repeatedly. Path traversal and escaping symlinks
  are rejected; PHP files are not served by this static handler.
- Admin HTML and API responses continue to use `Cache-Control: no-store`.
- Local PHP was restarted with OPcache enabled and timestamp validation.
  Runtime config: `tmp/local-integration-20261007/php-admin-optimized.ini`.
- Laravel config and route caches were built successfully locally. After
  editing config/routes, rebuild those caches or run `config:clear` and
  `route:clear` during development. Do not ship the generated local config
  cache; it contains local environment configuration.

These server changes apply to the local gateway/PHP runtime. Production
Apache/Nginx asset headers, compression and PHP OPcache must be configured
on the actual host. No live hosting changes or deployment were performed.

## Evidence and limitations

In runs with browser HTTP caching disabled, dashboard readiness improved
from 24,989ms to 7,185ms and category readiness from 10,187ms to 2,995ms.
Dashboard resource transfer dropped from 2,416,730 to about 755,000 bytes.
These are local observations, not a stable production benchmark.

A later run with HTTP cache enabled measured dashboard 17,292ms, categories
6,907ms, payments 11,562ms and media 7,478ms; incremental asset transfers were
39KB, 156KB, 394KB and 146KB respectively. Local timings vary substantially,
and backend rendering/bootstrap latency remains a limitation. Do not describe
all admin pages as consistently fast or the performance work as finished.

An isolated local payment-settings profile recorded 4 DB queries taking about
5ms in total; this sample does not support database queries as the primary
delay for that page. No speculative database indexes were added.

## Validation

- 10 browser checks passed: dashboard/category/payment/media at 320px,
  dashboard pagination and wrapping, opening native responsive details,
  payment layouts at 390/768/1440px, and no JavaScript page errors.
- 9 static-handler checks passed: gzip decoding, q=0 negotiation, ETag/304,
  HEAD behavior, cache invalidation, and rejecting admin/API/PHP/traversal
  requests from the asset handler.
- Screenshots were reviewed, including the final detail toggle position.
- Forms were read without saving hotel or payment records.

Frontend commands: `npm run test:admin-polish`, `npm run test:admin-assets`.
Browser screenshots/results: `test-results/admin-polish/after-results.json`.
