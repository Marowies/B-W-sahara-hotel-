# B&W Sahara Sky — standalone frontend

An independent implementation of the existing Sand & Copper / Burgundy design, with all 30 page templates and the original room model. This folder is separate from the PHP hotel application. Nothing is connected to live reservations, authentication, payments, email or WhatsApp.

## Run

Requires Node.js 20+ for the build tools.

```powershell
npm install
npm run build
npm run preview
```

Open `http://127.0.0.1:8780/?lang=en`. Arabic remains available via `?lang=ar`; the existing draft Chinese content is also retained. Preview binds to loopback only. `PORT` can select a different port.

The current `dist/` is already built. A standard static HTTP server can serve it without Node, PHP, a database, or npm dependencies. Do not open it with `file://`; ES modules, internal routes and the service worker require HTTP. Deploy at the origin root, preserving each route's `index.html` and genuine 404 responses.

## Folder layout

- `src/`: editable original page templates, styles, images, local fonts and frontend logic.
- `src/runtime.js`: route-view lifecycle, bounded view cache, singleton 3D loader, responsive image enhancement and local performance counters.
- `src/scene.js`: one renderer, demand-driven drawing, visibility/context handling and model controls.
- `src/dome-model.js` and `src/batch-model.js`: the existing room geometry and static mesh batching.
- `tools/build.mjs`: optimized WebP variants, content-hashed assets, compression, cache worker and build manifest.
- `tools/serve.mjs`: local static preview with Brotli/gzip, ETags and cache headers; it is not a booking backend.
- `dist/`: ready-to-serve output; generated, never edit directly.
- `tests/`: browser and geometric regressions.
- `docs/`: architecture, integration boundaries and verification notes.
- `test-results/`: screenshots and raw local verification output.

The old hosted-design checkout and the hotel PHP working copy remain independent. This work has not been published to the hosted design or the live hotel.

## Loading and stability

The first viewport loads page styling, required local fonts and responsive photography. It does not import Three.js, build the room, create a WebGL renderer or load its environment image.

When the model approaches the viewport, one shared promise imports the scene. Internal navigation retains the home's DOM and its canvas instead of reconstructing it. Full-screen mode moves that same model stage into the dialog and restores it on close. Camera, layout and scene objects stay allocated once within the document. The view cache holds at most eight routes and always retains the home view. Page scripts are active only for their own view.

Full reloads, language changes that reload the document, a new tab or an evicted browser cache can initialize a new scene. Browser/session caching avoids repeated asset transfer where supported; no implementation can guarantee “download once forever.”

The renderer draws only while visible and changing. It stops when idle, offscreen, on another route or in a hidden tab. Reduced motion remains respected. Device pixel ratio is capped at 1.75 on desktop and 1.25 on small screens; small-screen shadow maps are 512px instead of 1024px. Materials, layout, window pivots and the illustrated room boundary are preserved.

WebGL failure retains a photographic fallback and retry action. Context loss pauses rendering; restoration rebuilds the GPU environment without constructing a second model/canvas. No model asset is eagerly prefetched by the service worker.

## Caching and deployment

- JS/CSS/images/fonts use content-derived filenames. HTML and `sw.js` must be revalidated.
- Serve hashed resources with `Cache-Control: public, max-age=31536000, immutable`.
- Serve the emitted `.br` or `.gz` files with the corresponding `Content-Encoding` and `Vary: Accept-Encoding`. Their content type must remain JS/CSS/HTML.
- `_headers` is a starting template for compatible static hosts. Configure equivalent rules on other hosts; the preview already applies them.
- The service worker caches the small shell and only images/3D resources actually requested. Each cache is bounded to 100 entries; browser quota errors do not break navigation. HTML uses network-first with a previously visited route as fallback.
- The worker retains the current and one earlier cache generation. For a real deployment, retain the previous release's hashed assets on the server as well: an old open tab may request a model it has not loaded before. Do not delete old assets immediately or force a reload during interaction.
- No private/personal data is stored in these caches. Preview stay choices use the original local frontend behavior; there is no booking API.

## Editing and tests

Change `src/`, then run `npm run build`. Font files are self-hosted WOFF2 and shipped with OFL licenses. Latin and Arabic coverage is retained; the Noto Sans SC subset includes the Chinese characters in the current templates. Adding Chinese content requires updating that subset. Font preparation is optional maintenance, performed offline with Python fontTools and Brotli; it is not required for an ordinary build. No project text is sent to font services.

```powershell
node tests/verify-model.mjs
npm test
```

The browser test expects Chrome at the usual Windows installation path. Set `HOTEL_BROWSER_PATH` for another Chromium executable, or install Playwright's Chromium and adapt the path. `HOTEL_TEST_URL` can point at another local preview origin. Tests use software WebGL to make scene checks possible in headless mode; their timing is not a physical-device or production Core Web Vitals benchmark.

`window.hotel3D.getStats()` and `window.hotelPerformance` provide local diagnostic counters without changing the visual UI. No analytics service is connected.

## Later integration

Keep the client navigation shell and persistent model owner when integrating the approved frontend. Replace preview data/forms through explicit adapters. Do not copy the standalone service worker onto authenticated CMS/admin/payment routes; it currently handles only the independent static site. Backend integration, real booking policies and payment-link handling remain outside this deliverable.
