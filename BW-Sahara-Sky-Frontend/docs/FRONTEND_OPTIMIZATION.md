# Standalone frontend delivery — 5 October 2026

Source design: https://sahara-sky-amber-burgundy.upbeat-koala-7268.chatgpt.site/?lang=en

The implementation reuses the local `sahara-sky-design/dist` templates and assets for this design. The independent frontend contains 30 static page shells, the burgundy/sand/copper styling, photography, animations and the existing illustrative room model. The original source checkout, hosted site and hotel PHP application were not modified by this frontend work.

## Implemented

- One lazy import promise, one room construction and one WebGL renderer per document. Internal page navigation retains the home view and the same canvas; immersive mode moves that canvas rather than creating another one.
- The 3D library and scene are not requested above the fold. Loading starts near the model or when immersive mode is requested.
- Drawing stops when the model is idle, offscreen, on another route or in a hidden tab. Camera/light settling uses elapsed time, and reduced motion is respected.
- Device pixel ratio and mobile shadow resolution are capped. Static geometry is batched without dropping its triangles or changing room layouts/articulated window pivots. The subsequent interior refinement adds closed bathroom wall surfaces, oak trim and more natural chair geometry.
- WebGL failure displays a photo with a retry action. Context loss/restoration handling is implemented; explicit forced GPU context restoration was not included in the automated suite.
- Responsive WebP images, explicit image dimensions, lazy secondary images and locally hosted WOFF2 fonts remove unnecessary transfer and third-party font requests.
- Content-hashed asset names, Brotli/gzip output, ETags and immutable asset headers. A bounded service worker caches assets after use and retains one previous cache generation.
- A bounded eight-view route cache with view-owned listeners and observers keeps inactive views from handling interactions. Native navigation remains the fallback if a transition fails.
- Dynamic room photo paths were corrected to use the hashed asset map; previously missing thumbnails now resolve.

## Measurements

| Item | Before | Optimized |
| --- | ---: | ---: |
| Base photography | 7,843,465 bytes | 3,797,728 bytes (52% reduction) |
| Model meshes (including the refined interior) | 413 unbatched | 83 batched |
| Draw calls in the inspected view | 379 in the original preview | 101 with the refined interior |
| Model triangles in that view | 16,773 in the original preview | 29,654 with added cloth/wall/chair/lamp detail |
| Scene builds / renderer instances after home → rooms → home | — | 1 / 1 |

Responsive image variants add 3,243,204 bytes to the distribution, but browsers select an appropriate candidate rather than downloading every variant. Base image savings do not describe the size of the complete ZIP or the entire distribution. Fonts total 785,064 bytes on disk, including multiple languages/weights; browsers fetch only the faces they use.

## Verification

`tests/verify.mjs`: **16 passed, 0 failed** on Chrome headless with software WebGL, against the local static preview. Coverage includes initial lazy loading, singleton scene/renderer, idle/offscreen rendering, immersive canvas reuse, king/twin and cutaway controls, internal navigation, all normal English page routes, selected English/Arabic mobile routes, reduced motion, WebGL fallback, asset responses, cache headers, compression and service worker activation.

No JavaScript exceptions, missing asset responses, unexpected external HTTP asset requests or backend requests were observed by the suite. The 404 template is shipped but excluded from the normal-page sweep.

`tests/verify-model.mjs`: **passed**. All 43,744 inspected bathroom, recessed-entry, curtain and lounge vertices stay within the dome boundary. Shell alignment, king/twin visibility, level wall tops, curtain separation and independent door-glazing visibility are also checked. The entry header retains all four rectangular top corners. All three throws stay within 0.005 model units of their cover support surface, and the king/twin layouts contain three complete lamp assemblies in total.

Raw browser results are in `test-results/verification.json`; desktop, interior and mobile screenshots are in that folder. Timing and layout-shift observations in the raw report depend on local machine load, including software rendering. The accumulated CLS counter includes scripted scrolling and route transitions, so it is not a route-isolated field measurement. These timings are diagnostic observations, not production Core Web Vitals or physical-device guarantees.

## Interior and footer refinement

Following the customer's marked screenshots, the bathroom footprint was moved inwards and reduced so its four solid walls have a shared horizontal top and continuous timber caps. No sloped roof-height cuts remain. The front privacy wall has oak battens and a framed doorway. Fixtures are repositioned within the compact enclosure behind the bed.

Each lounge chair uses its own local frame, with the back behind the seat, rounded cushions, curved armrests and splayed legs. The oversized rear timber slab was replaced by slim posts and a narrow top rail. Two chairs face inward around a small table.

Curtains are narrow gathered cloth surfaces with smooth indexed normals, level top/bottom hems and curved rails. Their geometry is no longer clipped into faceted sheets. They clear the lounge bounding box by 0.146 model units and the entry by 1.034 units along the separating Z axis; these are model units, not surveyed physical dimensions. Door glazing has its own material and remains visible when the shell is hidden. Floorboard corners remain inside the circular deck.

`tests/verify-model-angles.mjs` checks four camera directions, captures the marked front/rear/side views and confirms a single model instance with no JavaScript exceptions. The corresponding screenshots are `test-results/model-angle-0.png` through `model-angle-3.png`, plus the top view.

The subsequent door/bed/lighting correction sets the side entry further inwards and constructs it from complete rectangular components without clipping. Its glazing stays independent of the shell visibility. The throw uses a horizontal support surface derived from the actual burgundy cover bounds; its slight rotation is in the horizontal plane, and folds rise by at most a few millimetres. It no longer uses a tilted plane suspended above the mattress. Bedside lamps have a metal base, stem, bulb, open tapered shade and rim details, without adding shadow-casting lights or external assets. `test-results/model-bed-level.png` verifies the view where the customer originally observed the floating fabric.

Every page uses the shared footer: five inline SVG icon links for Instagram, Facebook, TikTok, WhatsApp and email, without visible service names. Accessible labels, keyboard focus indicators and 44px targets remain available. Footer columns switch to a two-column layout and then stack the location section on small screens. There is no icon library or external icon request.

`tests/verify-refinements.mjs` covers the footer on home/rooms at 320, 390, 768, 1024 and 1440px in English and Arabic (20 combinations), plus repeated navigation with a pending model frame. A navigation race found during verification was fixed by refusing to render while the persistent stage is detached from the document.

## Delivery boundaries

This is a visual frontend preview. Reservation, availability, authentication, payment and contact flows have no live backend connection. Existing preview wording/data must be reviewed before integration. No live booking or payment test was performed.

“Load once” applies to navigation within the current document. A full reload, language-triggered reload or new tab initializes a new document; browser/service-worker caching can reuse transferred assets. Browser storage eviction remains possible.

Before public release, test representative real phones and deployment headers, keep previous hashed assets accessible for open tabs, review translations and obtain hotel approval of the illustrative room dimensions/layout. The standalone service worker should not be carried over to authenticated backend/admin/payment routes.
