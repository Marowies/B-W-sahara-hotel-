# Consolidated guest journey fixes

Includes the team UI changes and quiet account design. Ordinary email sign-in now continues to the localized account overview; explicit local booking/account return destinations take precedence. The existing session-storage guard prevents automatic redirect loops and the guest shell clears it after arrival. External and unrelated destinations are rejected.

The booking-start API accepts only en/ar/zh for its optional locale, returns it in the form URL and saves it as the page locale. Older clients without the locale remain supported. It still creates a session selection only, not a reservation. Customer arrival labels now distinguish noon and midnight; underlying stored option values are preserved.

Room cards reapply the selected category after hydration/pagination. Cookie preferences remain accessible after the initial choice, retaining the team's compact mobile banner. Operational booking copy no longer calls the steps a Preview.

Privacy/terms remain drafts: corrected false statements about disconnected accounts and availability, without inventing retention periods, legal identity, refund terms or hotel approval. Hotel approval remains required; see the user-facing checklist.

Validation: security regressions (including all three locales and invalid-locale rejection), routing gate 35 cases, destination/filter regression, consent persistence, release-update and deployment configuration tests passed locally. Connected noindex build produced 93 pages. Browser spot-check verified one persistent cookie-settings control and no repeated banner after saving necessary-only; operational booking intro no longer contains Preview. Full OAuth, MySQL concurrency, payment sandbox and Hostinger rewrite behaviour remain separate acceptance checks. No Staging or Production changes made.
