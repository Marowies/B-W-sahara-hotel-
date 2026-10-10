# Performance deployment on the existing hotel hosting

The user chose the current hotel hosting. The hosting plan, server software, CDN account and administrator access have not been verified. All changes in this task are local; nothing has been published or purchased.

## What is ready

- Frontend images: WebP, responsive sizes, maximum photo width 1920px, lazy loading except priority hero/brand images. Original reference images remain intact.
- JavaScript/CSS: esbuild minification, a separate inner-page render chunk excluded from home, existing dynamic Three.js/scene chunks, one shared CSS bundle. Scripts remain deferred; 3D is loaded on demand and retains one renderer across navigation.
- Hashed static assets: long-lived immutable caching, conditional ETags, Brotli/gzip build artifacts. Generated Apache `.htaccess` enables text compression if `mod_deflate` is available and cache headers if `mod_headers` is available. Existing Vercel CDN headers are also retained, but Vercel is not assumed to be the chosen production host.
- Server-side catalogue DTO cache: 30 seconds, locale/page/size/generation scoped. Room, currency and slug model saves/deletes invalidate after transaction commit. Direct SQL/bulk writes bypass model events; the TTL bounds their staleness.
- Browser catalogue cache: memory only, 15 seconds, bounded to 32 entries, failed requests evicted. Description/rate displays may therefore lag edits briefly; payable quotes and inventory must always be recalculated on the server.
- Catalogue and availability pagination: bounded page sizes, stable catalogue ordering, previous/next controls, busy-state skeleton. Availability has no cache.
- Public catalogue JSON gzip: only successful catalogue responses above 1 KiB, with encoding negotiation and `Vary`. Auth, CSRF, account, checkout and payment payloads are excluded. HTTP catalogue responses still use `no-store` so CDN caching never stores Laravel session cookies.
- Database migration: `2026_10_09_120000_index_frontend_room_catalogue.php`, composite index `(status, order, id)`. Applied and rollback/reapply tested only on the local database clone. Existing inventory/auth indexes are retained.
- Input/resize debounce; existing animation-frame scroll updates and offscreen 3D suspension retained. This frontend uses DOM scripts, not React: no speculative React memoization was added.
- Dependency review: all direct packages have a concrete build/runtime/audit use. Sharp upgraded from 0.35.4 to patched 0.35.5; npm reported zero known vulnerabilities after installation. esbuild and Lighthouse are development tools, not browser bundles. No required dependency removed blindly.

## Production configuration still required

| Requested item | Existing-host deployment requirement |
|---|---|
| CDN | Enable the provider CDN or an approved CDN account on the actual DNS zone. Cache only hashed static assets; bypass `/admin`, `/api`, checkout, callbacks/webhooks and any response with authorization/cookies or `no-store`. Do not enable “cache everything” on authenticated HTML. Confirm HTTPS, purge behavior and a new asset hash after deployment. No CDN account or DNS was changed here. |
| Load balancer | Requires at least two application instances and provider support. Shared hosting may not expose this. Before enabling: shared `APP_KEY`, shared session/cache/queue storage, uploads/object storage, database, trusted proxy configuration, health checks and consistent deployments. Do not claim a reverse proxy pointing to one PHP server supplies high availability. No load balancer was installed. |
| Database connection pooling | Verify database proxy/provider support and connection limits first. Size PHP-FPM workers below the DB connection budget, and measure connection wait time. A supported ProxySQL/provider pool can be added when infrastructure permits, with transaction/session behavior tested against booking row locks and refresh rotation. PDO persistent connections are not a substitute for a bounded pool; no persistent-connection toggle was enabled. |
| Shared server cache | The configured Laravel cache store is used locally. Multiple replicas require a shared supported Redis/database cache and a security queue worker. Cache only explicitly public DTOs and expensive immutable calculations; do not cache transaction state, available inventory or guest payment data. |
| API compression | Catalogue middleware is ready. Avoid additional proxy gzip on already encoded responses. Wider API compression needs endpoint review because reflected secrets can create compression side channels. |
| Database upgrade | Back up production and verify the table schema/index name; apply the isolated migration in an approved deployment window. On a large table, assess DDL lock duration first. |
| Cron/queue | Keep daily token pruning and the security notification queue configured separately; these were not activated on the live hosting. |

## Acceptance before production

1. Verify the existing hosting plan and supported PHP/web-server/CDN/database features.
2. Build with the approved production origin/indexability settings; local preview intentionally remains `noindex`.
3. Deploy on staging using HTTPS and same-origin backend routing. Test cache headers, login/logout and sensitive-path bypass with anonymous and authenticated requests.
4. Run Lighthouse on staging without competing browser jobs and collect real-user Core Web Vitals after release. Local scores are lab measurements, not proof of production performance.
5. Load test the actual hosting and realistic data volume before deciding whether additional instances/pooling are justified. The current small local catalogue cannot establish high-load database capacity.

References: [Lighthouse documentation](https://developer.chrome.com/docs/lighthouse/overview/), [Laravel cache API](https://api.laravel.com/docs/12.x/Illuminate/Cache/Repository.html).
