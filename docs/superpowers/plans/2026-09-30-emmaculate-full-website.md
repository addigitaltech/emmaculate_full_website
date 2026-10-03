# Emmaculate Full Website Implementation Plan

> **For agentic workers:** Implement task-by-task; keep each domain boundary and acceptance checks intact.

**Goal:** Build one installable, modular Laravel school platform from the inspected website and results-portal repositories, then deliver it as `emmaculate-full-website.zip`.

**Architecture:** A server-rendered Laravel monolith uses shared identity and persistence with domain boundaries for Website/CMS, Media, SchoolManagement, Results, Payments, Audit, Notifications and IntegrationApi. Blade/Vite/Tailwind/Alpine preserve the public website's presentation; protected admin and role portals use separate layouts and server-side policies. PostgreSQL is the default local/production profile, and the ZIP includes environment templates—not credentials.

**Tech Stack:** PHP 8.3+, Laravel 12, PostgreSQL, Blade, Vite, Tailwind CSS, Alpine.js, Laravel sessions/CSRF, Sanctum for authenticated JSON APIs, PHPUnit/Pest-compatible Laravel tests, Laravel HTTP client for gateways.

**Spec:** `SPEC.md`; source truth: `docs/architecture/source-feature-map.md`.

## Global Constraints

- “The final output must be ONE Laravel application.”
- “Do NOT modify the original Public Website repository.”
- “Do NOT modify the original Results Portal repository.”
- “The existing Public Website is the source of truth for the public website's visual design and existing school content/assets.”
- “The existing Results Portal is the source of truth for its existing functionality and business rules.”
- “Gateway secret keys must NEVER be exposed to frontend JavaScript.”
- “Never trust a frontend payment-success response as proof of payment.”
- “A student must NEVER be able to access another student's records.”
- “A parent must ONLY be able to access students explicitly linked to their account.”
- “Teacher access must be enforced server-side.”
- “Do NOT automatically block results based on payment unless the school explicitly enables such a rule.”
- “Do NOT pretend that an unsupported Moniepoint product provides the same functionality as Paystack or Flutterwave.”
- “If credentials/product access are unavailable, implement the driver architecture and configuration interface without inventing credentials or fake API behaviour.”
- Never include `.env`, real secrets, passwords, tokens, `vendor/`, `node_modules/` or caches in the ZIP.

## Review Focus

1. Teacher URL/API manipulation to reach an unassigned student/class/subject → feature tests on every list, score mutation and report policy.
2. Student or parent changing identifiers to access another student → policy tests using own/linked/unlinked records.
3. Tie ranks, missing/offered scores and third-term cumulative results → pure calculation tests against mapped source rules.
4. Forged, replayed or duplicate webhook delivery → raw-body signature and idempotency tests per implemented gateway.
5. Executable/polyglot uploads, unsafe navigation URLs or hostile rich text → validation tests for MIME/extension/size and safe scheme/block rendering.

---

### Task 1: Laravel foundation and shared identity

**Files:** Laravel root manifests/config; `app/Domain/IdentityAccess/**`; identity migrations/seeders; `routes/web.php`; `resources/views/auth/**`; `tests/Feature/IdentityAccess/**`.

**Produces:** Session authentication, password reset, roles/permissions, user profiles and policies with deterministic bootstrap roles. Shared contract: authenticated `User` has role/permission checks; every protected resource policy fails closed.

- [ ] Create the Laravel 12 project, pin dependency lockfiles, configure PostgreSQL environment names, Vite/Tailwind/Alpine, strict `.gitignore` and `.env.example`.
- [ ] Add role/permission tables and `User` assignments for Super Admin, Website Admin, Results Admin, Finance Admin, Teacher, Student, Parent and Content Editor.
- [ ] Implement session login/logout, password hashing/reset, throttling, secure cookie/session settings and role middleware/policies.
- [ ] Test login throttle, logout/session invalidation, role denial and protected-route access; run `php artisan test`.

### Task 2: Shared school settings, audit and media foundation

**Files:** `app/Domain/Media/**`, `app/Domain/Audit/**`, shared settings migrations/models/actions, `config/filesystems.php`, `tests/Feature/Media/**`, `tests/Feature/Audit/**`.

**Produces:** `MediaAsset` metadata/storage service, central `SchoolSettings`, audit writer, safe upload policy and public-media relation contract.

- [ ] Create media, school-settings and append-only audit migrations with ownership/index constraints.
- [ ] Implement MIME/signature, extension, size, safe filename, storage path and role validation; never permit executable uploads in image/document fields.
- [ ] Import the eight inspected school JPEGs into seed-managed media records; link logo, principal/proprietor photos and gallery records without hardcoding them into Blade templates.
- [ ] Seed only verified identity, public copy, navigation and factual leadership data; retain absent photos/placeholders as absent.
- [ ] Test invalid uploads, scoped media editing/deletion, safe storage paths and audit redaction.

### Task 3: Public website and dynamic CMS content model

**Files:** `app/Domain/Website/**`, public controllers/routes, website migrations/seeders, `resources/views/site/**`, `resources/css/**`, `resources/js/**`, `tests/Feature/Website/**`.

**Produces:** Premium public shell and dynamic data contracts for homepage, pages, history, identity content, academics, admissions, leadership, news, events, gallery, FAQs, announcements, navigation, footer and SEO.

- [ ] Port public route/page structure and the observed design tokens, typography, responsive hero, navigation interactions, cards, footer, accessibility and reduced-motion behavior into Blade/Vite views.
- [ ] Add typed CMS models/migrations/seed data for homepage sections/slides, custom pages/safe content blocks, history/milestones, mission/vision/pledge/anthem, academic programmes/curriculum, admissions, leadership, news/tags/categories, events, gallery/albums, FAQ, announcements, nav/footer and SEO metadata.
- [ ] Implement published/scheduled/expiry visibility rules and public-only queries; keep draft/private records inaccessible publicly.
- [ ] Connect public pages to database-backed settings/content/media, preserve supplied routes and avoid inventing missing school facts.
- [ ] Test route rendering, publication filtering, safe content blocks, schedule boundaries and SEO metadata.

### Task 4: Website administration and CMS workflows

**Files:** `app/Domain/Website/Actions/**`, admin controllers/requests/policies/routes, `resources/views/admin/website/**`, `tests/Feature/Admin/Website/**`.

**Produces:** One authenticated content-admin surface with CRUD, publish/schedule, order/visibility, media selection and granular permissions for every CMS area named in the brief.

- [ ] Implement admin dashboard navigation and cards grouped into Website, Results, Payments and System.
- [ ] Add authorized CRUD/forms for school identity/theme tokens, homepage, pages, about/history, mission/vision/pledge/anthem, academics/curriculum, admissions/applications, leadership, news, events, gallery, FAQs, announcements, navigation, footer and SEO.
- [ ] Add contact/admission persistence and administration; keep notification delivery configurable and report mail credentials as environment setup.
- [ ] Validate internal route/approved HTTPS navigation targets; prohibit `javascript:` and other unsafe schemes. Sanitize/render only the safe content-block schema.
- [ ] Test role boundaries, CRUD lifecycle, schedule/publication transitions, ordering and unsafe input rejection.

### Task 5: School management and academic structure

**Files:** `app/Domain/SchoolManagement/**`, relevant migrations/controllers/requests/policies/views/tests.

**Produces:** Students, teachers, parent households, classes, arms, class-arm associations, subjects, sessions, terms and teacher assignments with explicit relational constraints.

- [ ] Normalize profiles and user links; create many-to-many `ParentStudent` relationships; keep internal UUID/PK identifiers distinct from human student numbers.
- [ ] Add academic sessions/terms/current-period invariants, classes/arms/class-arms, subject scopes and class/subject teacher assignments.
- [ ] Implement admin CRUD and role-specific read scopes with FormRequests, policies and pagination.
- [ ] Test unique identifiers, household linking, current period constraints and teacher assignment isolation.

### Task 6: Result computation, entry, publishing and reports

**Files:** `app/Domain/Results/**`, result migrations, controllers/requests/policies, score-entry/report views, `tests/Unit/Results/**`, `tests/Feature/Results/**`.

**Produces:** Configurable assessments/scores/grades, affective/psychomotor ratings, remarks, server-derived statistics/ranks, publication workflow and printable/PDF-ready results.

- [ ] Implement assessment components and maxima; persist not-offered state; validate all score bounds server-side and transact batch edits.
- [ ] Migrate exact current A–F defaults and full 0–100 grade-band coverage validation.
- [ ] Implement source-mapped term totals, percentages, remarks, subject/class/arm rankings with competition ties (`count(higher)+1`), statistics and third-term cumulative averages over existing published terms only.
- [ ] Implement Draft → Pending → Published flow; student/parent queries return only authorized published results; teachers can change only authorized score resources.
- [ ] Port report-card visual structure and print/PDF delivery; add audit records for score edits/publication/result changes.
- [ ] Test formulas, offered/missing subjects, grade boundaries, tied ranks, class/arm scopes, cumulative rules and IDOR restrictions.

### Task 7: Portals and unified dashboard

**Files:** `app/Domain/IdentityAccess/**`, portal controllers/policies/routes/views, dashboard components, tests.

**Produces:** Role-specific Admin, Teacher, Student and Parent navigation/records within one Laravel app, backed by the same user/profile model.

- [ ] Implement Admin home and module navigation; replace source placeholder activity with real audit activity.
- [ ] Implement teacher class/subject/score-entry/review flows, student profile/results, and parent linked-child selector/results/payment history.
- [ ] Add secure report download/print routes that apply the same resource policies as HTML views.
- [ ] Test each role's permitted and forbidden transitions and mobile-usable layouts.

### Task 8: Fees, gateway adapters and receipts

**Files:** `app/Domain/Payments/**`, payment migrations/controllers/policies/routes/views, provider webhook routes, config/environment templates, `tests/Unit/Payments/**`, `tests/Feature/Payments/**`.

**Produces:** Flexible fee obligations, provider abstraction, Paystack and Flutterwave adapters, honest Moniepoint capability boundary, verified transaction state machine, webhook idempotency, history and receipts.

- [ ] Model session/term/class/student/fee type/amount/due date/status; allow student-specific charges and explicit configurable payment gates (default off for result access).
- [ ] Define `PaymentGatewayInterface` for initiate, verify, webhook authentication/parse, and supported refund; implement Paystack and Flutterwave with server-only config.
- [ ] Implement the Moniepoint adapter boundary/configuration status from verified POS/API capability only; refuse unsupported online checkout rather than simulate it.
- [ ] Persist unique secure references, user/student/parent links, amount/currency/provider/status/timestamps; keep raw card data and secret metadata out of records/logs.
- [ ] Verify provider signatures on raw request bytes/headers; record webhook/event identities and process state changes atomically/idempotently; rate-limit initiation/verification.
- [ ] Generate branded receipt and payer history only after server verification; never trust redirect query parameters or browser success.
- [ ] Test HMAC/header verification, altered payloads, duplicate/replayed events, incorrect amount/reference, failure/cancel/refund state and receipt visibility.

### Task 9: SEO, security hardening and operational documentation

**Files:** route/controllers/middleware/security config, sitemap/robots controllers, `Dockerfile`, `.dockerignore`, `.env.example`, `README.md`, security tests.

**Produces:** Production-oriented deployable configuration and operator guidance for installation, migrations, seeds, admin creation, gateway setup, backups and security.

- [ ] Add canonical/OG/SEO metadata for all public content; semantic HTML, alt text, sitemap, robots policy and clean routes.
- [ ] Configure production-safe errors, HTTPS/secure-cookie behavior, CSP/frame-compatible headers, MIME/referrer policies, CSRF, CORS allowlist and API rate limits.
- [ ] Add container/runtime recipe with ephemeral-file caution; use database/object storage configuration for durable user media and document backup/restore responsibilities.
- [ ] Document install/test/deploy, seed defaults, first-admin bootstrap, roles, media storage, provider constraints, backups and known missing credentials/data.
- [ ] Test headers, unauthenticated exposure, upload protections, secret/log redaction and public/private cache behavior.

### Task 10: Build verification and ZIP delivery

**Files:** archive only; build reports in `docs/` as appropriate.

**Produces:** `emmaculate-full-website.zip` containing one installable Laravel source project and no secrets/dependency caches.

- [ ] Run Composer validation, database migration/seed checks using a local test database when available, `php artisan test`, Vite production build and route/config inspections.
- [ ] Check README instructions against a clean install path and list unconfigured production integrations explicitly.
- [ ] Create ZIP with `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `tests/`, docs, lockfiles, assets, `.env.example`, and required Laravel files; exclude `.env`, credentials, `vendor/`, `node_modules/`, temporary files and caches.
- [ ] Verify ZIP inventory, archive integrity, and source repo working trees; link the final archive by absolute path.
