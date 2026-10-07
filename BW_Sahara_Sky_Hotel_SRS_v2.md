# Software Requirements Specification (v2 — Merged)
## B&W Sahara Sky Hotel — Official Website Redesign

**Version:** 2.0 (Draft — pending codebase access)
**Conforms to structure of:** IEEE 830 / ISO/IEC/IEEE 29148
**Target Execution:** Codebase Evaluation Agent, Technical Lead, Engineering Team
**Source:** Stakeholder video review, 2026-09-26 (41 min), plus prior analysis drafts v1.0 and "Master Package" v1.1

### Attribution Legend (applied throughout)
- **[VIDEO]** — stated by a stakeholder in the source meeting
- **[FACT: PENDING]** — a codebase fact that cannot yet be confirmed; **no repository has been provided to this session**. Every such tag is a placeholder for verification, not a finding.
- **[REC]** — an AI/engineering recommendation, not something the client asked for verbatim

---

## 1. Introduction

### 1.1 Purpose
Defines functional and non-functional requirements for the B&W Sahara Sky Hotel website redesign, structured for direct use by an engineering team or a codebase-auditing AI. **[REC]** Nothing in this document should be treated as confirmed technically feasible until Section 3 (Codebase Audit) is actually completed against the real repository.

### 1.2 Scope
**In scope [VIDEO]:** public website, booking search widget and handoff to the EUCL/EGY Cloud booking engine, Admin Dashboard/CMS, SEO/metadata layer, mobile responsive layout, social-channel links.

**Out of scope [VIDEO]:** replacing the EUCL/EGY Cloud PMS or booking engine backend; new languages beyond English/Arabic/Simplified Chinese; the parasite-domain legal/takedown process; payment processing (not mentioned in source material — **[FACT: PENDING]** whether payments run through the PMS or the site).

### 1.3 Definitions
| Term | Definition |
|---|---|
| EUCL / EGY Cloud | Third-party vendor hosting the booking engine/channel manager |
| FIT | Free/Foreign Independent Traveler |
| RedBook / Xiaohongshu / 小红书 | Chinese social discovery platform — primary discovery channel for ~30% of guests |
| Parasite site | Unauthorized clone site diverting brand traffic to Booking.com for affiliate commission |

### 1.4 References
- Stakeholder video review, `2026-09-26 16-16-15.mp4`, timestamps `00:00`–`39:40`
- EUCL/EGY Cloud vendor API documentation — **[FACT: PENDING]**, not yet obtained

---

## 2. Overall Description

### 2.1 Product Perspective
The site is an acquisition channel competing against OTAs and parasite clones for the same demand, dependent on an external booking engine it does not control. **[REC]** The single highest-risk integration point is the handoff fidelity between the two systems (§4 FR-002).

### 2.2 User Classes
| Class | Description | Notes |
|---|---|---|
| Prospective Guest (FIT) | ~60% Far East (incl. ~30% Chinese), ~40% Western [VIDEO] | Mobile-first, low friction tolerance |
| Travel Agency Coordinator | Books multi-guest packages via agency channel, not direct site [VIDEO] | Not detailed further in source |
| Content Administrator (Hania) | Manages rooms/pricing/photos [VIDEO] | Non-technical; CMS user |
| Marketing/Ops Staff (Mai) | Keywords, brand assets, social presence [VIDEO] | Non-technical |

### 2.3 Operating Environment
Target browsers: iOS Safari, Chrome Android [VIDEO]. Mobile viewport range 360–430px; desktop up to 1920px [VIDEO].

### 2.4 Constraints (binding, not negotiable at this stage)
| Constraint | Source |
|---|---|
| Logo (icon, typography, shape) must not be altered | REQ-015 [VIDEO] |
| Core palette — Beige, White, Brown — must not change | REQ-015 [VIDEO] |
| Language scope fixed at EN/AR/Simplified Chinese | RULE-003 [VIDEO] |
| Content hierarchy fixed: Rooms (1st) → Safari/Tours (2nd) → F&B (never a standalone lead magnet) | RULE-001 [VIDEO] |
| PMS/booking engine vendor fixed — no backend replacement | Business Context [VIDEO] |

### 2.5 Assumptions and Dependencies
- Assumes EUCL/EGY Cloud can accept some pre-fill mechanism — **[FACT: PENDING]**, unconfirmed; blocks FR-002.
- Hotel must supply new high-resolution room photography for FR-004 — client dependency, not engineering.
- Client must supply the finalized SEO keyword inventory referenced but not delivered in the source meeting — client dependency, blocks FR-006.
- Full removal of the parasite site depends on a domain/trademark dispute outside engineering's control; SEO can only compete with it, not eliminate it.

---

## 3. Codebase Audit & Gap Analysis (Template — Pending Repository Access)

**[REC]** This section is the mechanism the Master Package correctly calls for: every requirement below must be classified into one of four states once the actual repository is available. Until then, every row reads **UNKNOWN — NOT YET AUDITED**. Do not fill this table from inference or assumption; leave rows blank rather than guess.

| Req ID | Area | Classification | File/Line Evidence | Notes |
|---|---|---|---|---|
| FR-002 | Booking search → EUCL handoff | UNKNOWN — NOT YET AUDITED | — | Check form submit handler, routing logic |
| FR-004 | Room card image model | UNKNOWN — NOT YET AUDITED | — | Check for `image: string` vs `images: string[]` |
| FR-005 | i18n routing/dictionaries | UNKNOWN — NOT YET AUDITED | — | Check `en.json`/`ar.json`/`zh.json` and route middleware |
| FR-008 | Booking-engine theming/handoff | UNKNOWN — NOT YET AUDITED | — | Check redirect vs. iframe implementation |
| FR-010 | Admin auth/session model | UNKNOWN — NOT YET AUDITED | — | Check auth tables, role scoping |
| NFR-001 | Responsive CSS/Tailwind config | UNKNOWN — NOT YET AUDITED | — | Check breakpoints, fixed widths, unconstrained `<img>` |

Classification key: **EXISTING & WORKING** / **PARTIAL / MISCONFIGURED** / **COMPLETELY MISSING** / **REFACTORING REQUIRED**.

---

## 4. Functional Requirements

Each requirement is tagged **[VIDEO]** for the requirement itself; any implementation suggestion is separately tagged **[REC]**. Acceptance criteria are written in Gherkin (Given/When/Then) per the stronger convention from the Master Package.

### FR-001 — Direct Room & Safari Package Reservation **[VIDEO: REQ-001]**
- **Actor:** Prospective Guest
- **Requirement:** Guest can select dates, view availability, select a safari/excursion package, and initiate booking without phone/email.
- **Priority:** P0
- **Acceptance Criteria (Gherkin):**
  ```
  Given a guest is on the homepage or accommodations page
  When they select check-in/check-out dates, occupancy, and an optional package
  And they submit a valid search
  Then they are handed off to the booking engine reflecting that exact search
  ```
- **Dependencies:** FR-002, FR-003.
- **Codebase check:** §3 row FR-002 (shared handoff mechanism).

### FR-002 — Booking Search Input Validation **[VIDEO: implicit in REQ-009]**
- **Requirement:** Check-out must be after check-in; occupancy must be non-negative integers, validated before any handoff is constructed.
- **Priority:** P0
- **Acceptance Criteria (Gherkin):**
  ```
  Given a guest has opened the date/occupancy widget
  When check-out date is on or before check-in date, or occupancy is missing/invalid
  Then submission is blocked and an inline error is shown
  When both are valid
  Then the search proceeds to FR-003
  ```
- **Note [REC]:** Exact error-state wording/UX is not specified in source material; confirm with client if not already implemented.

### FR-003 — Booking Engine Deep-Link Parameter Pass-Through **[VIDEO: REQ-005, REQ-009]** — **P0, Critical/Blocking**
- **Current situation [VIDEO]:** Clicking through resets to today's date on the booking engine; parameters are dropped.
- **Acceptance Criteria (Gherkin):**
  ```
  Given a guest selects check-in D1, check-out D2, A adults, C children
  When they click "Book Now"
  Then the booking engine's landing page displays availability for D1–D2, A adults, C children
  And no re-entry of search criteria is required
  ```
- **Open issue [VIDEO/AMB-001]:** Exact EUCL parameter syntax/handoff mechanism (GET query string vs. POST/session) is unknown — blocks final design. See §11 CLARIFICATION-001.
- **Codebase check:** §3 row FR-002.

### FR-004 — Multi-Image Room Gallery / Carousel **[VIDEO: REQ-019]** — P1
- **Requirement:** Room cards render an array of images in a swipeable/clickable carousel with pagination, on desktop and mobile.
- **Acceptance Criteria (Gherkin):**
  ```
  Given a room with more than one photo
  When a guest swipes or clicks the room card's image area
  Then all available photos for that room are navigable with visible pagination indicators
  ```
- **Dependency:** Hotel must supply additional photography — client dependency.
- **Codebase check:** §3 row FR-004.

### FR-005 — Multilingual Route & Content Parity **[VIDEO: REQ-013, REQ-018]** — **P0, Critical/Blocking**
- **Current situation [VIDEO]:** Services page disappears in Arabic; card counts differ (5 in Arabic vs. 4 in English on the Explorer section).
- **Acceptance Criteria (Gherkin):**
  ```
  Given the site is loaded in English, Arabic, or Simplified Chinese
  When a guest switches language from any page
  Then the same route/page renders in the target language
  And the number of content cards per section is identical across all three languages
  ```
- **Open issue [VIDEO/CLARIFICATION-003]:** Unknown whether the extra Arabic card is a missing translation (add) or an intentionally domestic-only offering (remove) — must be resolved with the client before fixing.
- **Codebase check:** §3 row FR-005.

### FR-006 — Social & Regional Channel Integration **[VIDEO: REQ-010, REQ-011]**
- **Requirement:** Add Instagram, TikTok, LinkedIn, and RedBook (Xiaohongshu) links to header/footer/contact, alongside existing WhatsApp/email/Facebook.
- **Priority:** RedBook — P1 (30%+ of guests are Chinese and it's their primary discovery channel [VIDEO]); Instagram/TikTok/LinkedIn — P2.
- **Acceptance Criteria (Gherkin):**
  ```
  Given the site header, footer, or contact section
  When inspected
  Then working links to Instagram, TikTok, LinkedIn, and RedBook open the hotel's respective official profile
  ```
- **Dependency:** Hotel must supply/confirm official account URLs.

### FR-007 — SEO & Search Ranking Optimization **[VIDEO: REQ-004, REQ-012]** — P1
- **Requirement:** Implement meta tags, semantic HTML, structured hotel schema (schema.org LodgingBusiness), and keyword optimization in all three languages, using the client's keyword inventory.
- **Acceptance Criteria (Gherkin):**
  ```
  Given the client's finalized keyword inventory
  When applied to page titles, meta descriptions, headings, and structured data across EN/AR/ZH
  Then brand-name search ranking measurably improves relative to OTA and parasite-clone listings
  ```
- **Dependency [VIDEO]:** Client must deliver the finalized keyword document referenced but not yet provided.
- **Scope note [REC]:** This does not remove the parasite site — that is a separate legal/domain-dispute track (§2.5).

### FR-008 — Booking-Handoff Brand Continuity **[VIDEO: REQ-006, REQ-017]** — P1
- **Current situation [VIDEO]:** Navbar/footer disappear and colors shift abruptly entering the booking engine, causing guests to suspect a phishing redirect.
- **Acceptance Criteria (Gherkin):**
  ```
  Given a guest completes a valid search and proceeds to the booking engine
  When the handoff occurs
  Then header, footer, and the Beige/White/Brown palette remain visually consistent, to the extent the vendor allows
  ```
- **Open issue [VIDEO/AMB-001, CLARIFICATION-002]:** Unresolved whether the mechanism should be an embedded iframe/modal or a styled redirect — depends on whether EUCL supports CSS injection or header/footer wrapping. **[FACT: PENDING]**
- **Codebase check:** §3 row FR-008.

### FR-009 — Admin Content Management **[VIDEO: REQ-001 admin portion]** — P3
- **Requirement:** Authorized non-technical staff (Hania) can edit room rates, photos, descriptions in all three languages without developer involvement; changes reflect live.
- **Acceptance Criteria (Gherkin):**
  ```
  Given Hania is authenticated in the Admin Dashboard
  When she edits a room's price, photos, or description in any of the three languages and saves
  Then the change appears on the live public site
  ```
- **Codebase check:** §3 — confirm existing CMS actually supports multilingual field editing (not yet in the audit table above; add on inspection).

### FR-010 — Admin Access Revocation & Role Provisioning **[VIDEO: REQ-008]**
- **Requirement:** Revoke former administrator's credentials; provision Hania with an isolated, scoped account.
- **Priority:** P3 in source prioritization, but **[REC]** the credential-revocation half should be treated as an immediate security fix regardless of the source's P3 label — a stale privileged credential is a standing exposure independent of feature-delivery sequencing.
- **Acceptance Criteria (Gherkin):**
  ```
  Given the former administrator's credentials
  When an authentication attempt is made with them
  Then access is denied
  Given Hania's new credentials
  When she authenticates
  Then she is scoped only to content-management functions
  ```
- **Codebase check:** §3 row FR-010.

---

## 5. External Interface Requirements

### 5.1 EUCL / EGY Cloud Booking Engine — API Contract **[FACT: PENDING]**
- **Direction:** Outbound handoff, site → booking engine.
- **Mechanism:** Undetermined — query-parameter redirect vs. embedded iframe/session handoff (§4 FR-003, FR-008; AMB-001).
- **Data intended to exchange [VIDEO]:** check-in date, check-out date, adults, children, room count/type.
- **Status:** No vendor documentation obtained. **This is the single largest blocking dependency in the entire spec** — FR-003 and FR-008 cannot be finalized without it. **[REC]** Recommend this be the first item requested from the client, ahead of any development sprint planning.

### 5.2 Social Platform Links
Standard outbound hyperlinks only — no API/embedded-feed integration implied by source material.

### 5.3 Search Engine Interface
Standard SEO mechanisms: meta tags, schema.org structured data, sitemap, robots directives. No analytics/search-console integration described in source material.

---

## 6. Non-Functional Requirements

### NFR-001 — Mobile Responsiveness **[VIDEO: REQ-014, REQ-021]** — P0
- **Requirement:** No horizontal overflow, no distorted image ratios, full booking flow completable, across 360–430px mobile and up to 1920px desktop.
- **Reason [VIDEO]:** 80%+ of guests are on mobile.
- **Acceptance Criteria (Gherkin):**
  ```
  Given any page rendered at a viewport width between 360px and 1920px
  When inspected on iOS Safari or Chrome Android
  Then no horizontal scroll, clipping, or broken layout occurs
  ```

### NFR-002 — Page Performance & Cognitive Load **[VIDEO: REQ-021]**
- **Requirement:** Optimize asset payloads, script loading, layout shift.
- **Reason [VIDEO]:** Stakeholders described the site as slow and disorienting ("بطيء ومش مريح" / "تهنا في الويب سايت").
- **Note [REC]:** No numeric target (e.g., Core Web Vitals thresholds) is defined in source material — recommend agreeing specific metrics with the client rather than treating this as satisfied by subjective impression alone.

### NFR-003 — Visual Identity Compliance **[VIDEO: REQ-015, RULE-002]**
- **Constraint, applies to all visual work:** No logo alteration; no palette deviation from Beige/White/Brown.

### NFR-004 — Typography & Visual Hierarchy **[VIDEO: REQ-016]** — P2
- Modernize legibility across Latin/Arabic/Chinese scripts within the NFR-003 constraint. Subjective "luxury" quality should get explicit stakeholder sign-off since it isn't objectively measurable from the source material.

### NFR-005 — Footer Redesign **[VIDEO: REQ-020]** — P2

### NFR-006 — Administrative Security **[VIDEO: REQ-008 non-functional aspect]**
- Role-based auth; no stale credentials remain valid. Shared acceptance criteria with FR-010.

---

## 7. Data Model & Schema Modifications (Inferred — Pending Repository Access)

**[FACT: PENDING]** — the following are *implied* by the functional requirements above, not confirmed against any schema. Do not treat as accurate until verified in the actual codebase:

- **Room entity:** multilingual name/description (EN/AR/ZH), price, and an image field that may currently be `image: string` and would need to become `images: string[]` (FR-004).
- **Content Block/Card entity:** must exist per-locale in equal counts (FR-005) — exact current shape unknown.
- **Admin User entity:** needs role/permission scoping to separate a content-editor role from any legacy admin role (FR-010).
- **Social Link config:** needs a platform-type field that includes a Chinese-platform (RedBook) option not previously modeled (FR-006).

**[REC]** Once the repository is available, this section should be replaced with actual TypeScript interface diffs and any required DB migration notes, as the Master Package correctly specifies — fabricating those without seeing the code would misrepresent a recommendation as a fact.

---

## 8. Verification Matrix & Implementation Roadmap

### 8.1 Traceability (representative — full list in §4)
| Requirement | Business Need | Problem | Acceptance Criteria Ref. |
|---|---|---|---|
| FR-003 | Direct-booking revenue | Dates/occupancy reset on handoff | §4 FR-003 |
| FR-005 | Equal experience for all guests | Pages/cards vanish by language | §4 FR-005 |
| FR-006 | Reach 60%/30% Far East/Chinese market | Missing RedBook/social links | §4 FR-006 |
| NFR-001 | Serve 80%+ mobile audience | Unusable mobile layout | §6 NFR-001 |

### 8.2 Implementation Roadmap **[REC — sequencing proposal, not stated verbatim by the client]**
- **Phase 1 (P0 — must resolve before other work is meaningful):** FR-001, FR-002, FR-003, FR-005, NFR-001, NFR-002. These block the core business goal (direct bookings) and the core audience (mobile, international-language guests) respectively.
- **Phase 2 (P1 — high-value, not blocking):** FR-004, FR-006 (RedBook), FR-007, FR-008.
- **Phase 3 (P2/P3 — polish and operational cleanup):** FR-006 (remaining social links), FR-009, FR-010 (Hania onboarding portion), NFR-004, NFR-005.
- **Immediate, out-of-band (security exception):** FR-010's credential-revocation half — recommend doing this regardless of phase sequencing, since it's a standing exposure rather than a feature gap.

This phasing reuses the client's own stated priorities (source REQ table); it does not reprioritize anything the client did not already rank.

---

## 9. Things NOT to Change **[VIDEO]**
Logo (icon/typography/shape); Beige/White/Brown palette; EN/AR/Simplified-Chinese-only language scope; Rooms-and-Safari business positioning (not repositioned as a dining destination); EUCL/EGY Cloud vendor relationship.

---

## 10. Open Issues / Clarification Questions

1. **(Blocks FR-003, FR-008)** What exact parameter syntax or handoff mechanism does EUCL/EGY Cloud require/support (GET query string, POST/session, custom CSS injection)?
2. **(Blocks FR-005)** Is the extra Arabic-only content card a translation gap or an intentional domestic-only offering?
3. **(Blocks FR-007)** What is the specific target/metric for "outranking" OTAs and the parasite clone (e.g., top-3 for brand-name query)? Not defined in source material.
4. **(Blocks NFR-002)** What performance targets (e.g., Core Web Vitals thresholds) should "fast" mean? Not defined in source material.

---

## 11. Final Requirement Checklist
- [ ] Booking search validates dates/occupancy (FR-002)
- [ ] Deep-link handoff preserves dates/occupancy on the EUCL engine (FR-003)
- [ ] Room cards support multi-image carousel (FR-004)
- [ ] Route/card parity confirmed across EN/AR/ZH (FR-005)
- [ ] Instagram/TikTok/LinkedIn/RedBook links live (FR-006)
- [ ] SEO metadata/schema implemented in all three languages (FR-007)
- [ ] Brand continuity preserved through booking handoff (FR-008)
- [ ] Admin can edit multilingual room content live (FR-009)
- [ ] Legacy admin credentials revoked; Hania provisioned (FR-010)
- [ ] Zero mobile overflow/breakage across 360–1920px (NFR-001)
- [ ] Logo/palette unchanged throughout (NFR-003)
- [ ] Codebase Audit table (§3) fully populated from actual repository inspection — **currently 0% complete, pending repo access**

---

## Note for the Implementing Engineer / AI
Section 3 and Section 7 are templates, not findings — they must be completed against the real repository before any of this is scoped for effort estimation. Keep the **[VIDEO]** / **[FACT: PENDING]** / **[REC]** tags intact when you fill them in: replace **[FACT: PENDING]** with a cited file/line **[FACT: CONFIRMED — path:line]** only once you've actually inspected the code, and never convert an **[REC]** into a stated client requirement.
