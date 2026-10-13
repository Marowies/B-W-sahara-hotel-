# Booking, cookie preferences and traffic limits

Local changes, not yet deployed to staging or production.

- Public room search is available without an account. Selecting a room asks anonymous visitors to sign in and preserves their active booking selection. The booking API requires a customer access token. Native booking creation and token forms require an authenticated customer session; checkout retains token validation.
- Dynamic room cards resolve the build's optimized image aliases rather than requesting missing original JPG files.
- Unknown public routes return the site's existing branded 404 HTML with HTTP 404. Frontend navigation can display this response without treating it as a server failure.
- Cookie choices are necessary only or all, remembered for six months. Necessary login/session cookies and the active tab's booking data remain available. Optional analytics are silent until all is selected. All also permits seven-day local storage of dates, guest counts and room selection. Necessary only deletes this optional storage. Preferences can be changed in the footer. No OTPs, tokens or contact details are stored in that preference storage.
- Local preview limits document requests to 40 per minute per source IP. Laravel limits dynamic requests to 120 per minute per source IP, in addition to the stricter existing OTP/contact/API limits. Rejections use HTTP 429 and Retry-After. Static preview assets do not consume the document budget.

## Hosting requirements

The preview server is local tooling. Its document limit is not automatically deployed by copying static frontend files. Configure equivalent document limits and a branded ErrorDocument 404 at the production CDN/web server. Put public static files behind a cache; configure trusted proxies narrowly so Laravel sees the real client IP. For multiple application servers, use a shared Redis rate-limit cache. Source IP limits may group users behind a shared network and cannot uniquely identify hardware or stop a distributed denial-of-service attack. Edge protection is required before calling this production traffic protection complete.

## Validation

Standalone security regressions include anonymous native booking rejection and dynamic traffic bursts. Node tests cover preview limiter windows, independent clients and bounded memory. Local HTTP smoke checks cover booking, admin, branded missing-route status and anonymous API rejection. Real sign-in remains a user-entered OTP check; no live OTP is exposed in tests.
