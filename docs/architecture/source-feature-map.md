# Emmaculate Academy — Source Inspection & Feature/Architecture Map

**Inspection date:** 2026-09-30  
**Purpose:** satisfy the pre-implementation inspection gate in the supplied project brief. This document maps both existing repositories into one proposed Laravel application. No source repository files were modified during inspection.

## 1. Inspected source snapshots

| Repository | Commit inspected | Framework / runtime | Working tree |
|---|---|---|---|
| `addigitaltech/emmaculate-academy-website` | `9fa9c212e24b883b9f2912bca31259859040312b` — “Document CMS feature audit and implementation gaps” | Next.js 14 App Router, React 18, TypeScript, Tailwind; Next route handlers + `pg` + PostgreSQL | Clean (`main...origin/main`) |
| `addigitaltech/School_result_portal` | `849ce901a07d7897a1099d5dc983573172591e6b` — “V3: add token checker and portal access controls” | React 18, TypeScript, Vite, React Router; Node/Express API + PostgreSQL | Clean (`main...origin/main`) |

Inspected manifests, project trees, documentation/audits, routes, database schemas, auth and API code, representative page/component implementations, content modules, and public media. Repository inspection did **not** connect to any deployed service or database; production records cannot be inferred from repository files.

## 2. Existing architecture at a glance

```text
Public-site repository
  Next.js App Router public pages + static content/*.ts + public/images/*
       ├── small admin CMS (Next API handlers; Postgres)
       │     ├── users (admin/editor)
       │     ├── news_posts
       │     ├── singleton site_settings JSONB
       │     └── audit_log
       └── contact API (validates/rate-limits; currently discards submissions)

Results-portal repository
  React/Vite client (BrowserRouter; bearer token in localStorage)
       └── Express API (JWT + role-scoped generic /data/:table API)
             └── separate PostgreSQL schema for students, teachers, parents,
                 classes/arms, subjects, sessions/terms, scores, ratings,
                 remarks, settings, tokens, reset tokens, uploaded_files

Target: one modular Laravel application, shared identity/authorization and database,
with retained public-site presentation, Results domain, CMS, payments, media,
audit, notifications, and integration/webhook boundaries.
```

## 3. Public website → Laravel feature map

The visual reference is the existing Next.js public website, not the results dashboard. It uses a warm editorial layout, Fraunces display + Inter body typography, maroon/charcoal/gold tokens, responsive split-image hero, sticky header with click-driven desktop dropdowns and expandable mobile navigation, warm-neutral content surfaces, compact card treatment, footer and WhatsApp affordance. It includes keyboard focus visibility, skip link and reduced-motion handling. The public route group is distinct from `/admin` chrome.

| Existing feature / evidence | Existing state | Proposed Laravel domain/data | CMS + public rendering migration |
|---|---|---|---|
| Home: `SplitHero`, PortalStrip, Intro, StatsBand, AcademicsSplit, NewsEventsPreview, LeadershipHighlight, GalleryPreview, AdmissionsCta | Composed from static component data; home uses `SplitHero`, not the alternate `Hero` carousel component | `Website\Homepage`: ordered typed sections + `HeroSlide` records | Preserve section order, spacing, layout and breakpoints. Treat alternate `Hero.tsx` carousel as a reference/unused implementation, not the live home behavior. Make text/media/CTA fields dynamic without replacing presentation. |
| About | `/about`, static copy + image; mission/vision imported statically | `Website\Pages`, `SchoolProfile`, typed content blocks | Preserve route and layout; migrate confirmed school text and image associations; fields editable. |
| Primary/secondary history | `/history`, data in `content/history.ts` | `SchoolHistory` + ordered `HistorySection`/`Milestone` | Seed supplied factual narratives; support section ordering, visibility, images and milestones. Do not fabricate missing facts. |
| Mission, vision, pledge, anthem | `content/mission-vision.ts`; official wording | `SchoolIdentityContent` or typed settings/content records; optional audio media | Preserve wording; editable in CMS; audio stays unset until an official recording exists. |
| Leadership | `content/leadership.ts` with proprietor, principal, vice-principal, head teacher, bursar; two official headshots and three null photos | `LeadershipProfile` belongs-to `MediaAsset` | Seed names/titles/order/visibility; keep missing photos as null; admin can manage profile and media, public page retains card design. |
| Academics | `/academics`, `/academics/primary`, `/academics/secondary`; curriculum strings intentionally marked awaiting official content | `AcademicProgramme`, `CurriculumItem`, section/feature blocks | Preserve programme-specific pages. Seed approved overview, retain missing curriculum as unfilled/explicitly awaiting verified school input; do not invent subjects or activities. |
| Admissions | `/admissions`; introductory copy; requirements/process placeholders | `AdmissionSettings`, `AdmissionFaq`, optional private `AdmissionApplication` | Manage status, requirements, process, dates and FAQs. No persisted admission application is present in source; add only as an explicit new capability, with private application records. |
| News/blog | `/news`, `/news/[slug]`; DB-backed News CRUD is the only substantial end-to-end CMS content | `NewsPost`, author/user, category/tag relations, publication and SEO fields | Migrate schema and existing records if source DB export is later supplied; preserve draft/published state, sanitization and public-only published reads; extend schedule/SEO fields per brief. The repository includes no live database records. |
| Events | `/events`, `/events/[slug]`; empty static collection | `Event` | Empty content stays empty; admin CRUD/schedule/public filtering; preserve page/card pattern. |
| Gallery | `/gallery`; five static photos, three album labels, client lightbox/layout | `GalleryAlbum`, ordered `MediaAsset` relations | Import photos and captions/alt text as records; preserve current gallery layout/lightbox behavior; CMS supports albums, cover, ordering, visibility and alt text. |
| FAQ | `/faq`; static collection currently empty | `FaqCategory`, ordered `FaqItem` | CRUD/category/order/visibility; seed no fabricated questions. |
| Contact | `/contact`; validated frontend form, `POST /api/contact` Zod validation, honeypot and in-memory IP limit | `ContactEnquiry` + notification service | Existing endpoint returns success but neither stores nor sends real enquiry; connect persistence/admin workflow and notification, keep generic errors and rate limiting. |
| Branding/contact/social/theme | `content/site-settings.ts`; partial admin JSONB settings | `SchoolSettings` + controlled theme tokens/CSS variables | Migrate school identity/contact/social data, preserve placeholders where unconfirmed; ensure public layout, metadata, nav/footer and CTA read canonical DB-backed settings. Never accept arbitrary CSS/JS. |
| Navigation/footer | `content/navigation.ts`; static hierarchy/links | Ordered `NavigationMenu`/`NavigationItem`, `FooterSection`/`FooterLink` | CMS supports shallow tree, safe local routes/approved HTTPS URLs, ordering/visibility; public shell remains the existing shell. Block `javascript:` and unsafe schemes. |
| Portal cards/links | Five static entries all `coming-soon`, no real result/payment links | `PortalLink` | Preserve labels/descriptions and disabled/non-live behavior until actual unified Laravel routes are ready; link directly to internal portals after migration. |
| SEO/legal/robots/sitemap | Static metadata, static legal pages, route-based sitemap and robots | Per-page SEO metadata; legal pages as managed content if required | Keep URLs and metadata behavior; dynamic sitemap and canonical/OpenGraph data. |
| Existing assets | Eight JPEGs under `public/images`: `logo.jpg`, `principal.jpg`, `proprietor.jpg`, `lab.jpg`, `school-gate.jpg`, `students-group.jpg`, `students-reading.jpg`, `textbooks.jpg` | `MediaAsset` metadata and Laravel media storage | Copy binaries into new project's managed media storage and create initial records/relations; static path can be a compatibility/import source, not the permanent CMS source. Preserve the supplied photographs, no stock/AI substitutions. |

### Website source implementation boundaries observed

- Current public pages mostly read TypeScript constants; only News and a partial singleton settings object have database-backed CMS flows.
- Existing CMS routes/pages are login, dashboard, News list/create/edit, Site Settings, Users. Roles are `admin`/`editor`; there is no granular permission model.
- Website database tables are `users`, `news_posts`, `site_settings` JSONB singleton, `audit_log`.
- Site settings helper falls back to static content, but much of the public UI and metadata imports static settings directly, so CMS changes do not consistently update the public experience.
- No media upload/library abstraction exists in the website repository; media is bundled in `public/images`.
- Contact is not persisted/delivered. Events, gallery, leadership, academics, admissions, FAQ, portal links and navigation are not connected end-to-end to CMS.
- Existing security strengths to retain: password hashing, signed HttpOnly session cookie, login throttling/progressive lockout, `requireSession` API checks, Zod validation, sanitized news rich text, audit writes, CSP/security headers and accessible/reduced-motion UI. Known constraints: stateless 8-hour session cannot be individually revoked; CSP currently permits same-origin `unsafe-inline` scripts for Next hydration; audit UI is absent.

## 4. Results portal → Laravel feature map

| Existing feature / evidence | Existing state | Proposed Laravel domain/data | Migration notes |
|---|---|---|---|
| Authentication and recovery | Roles: `admin`, `teacher`, `student`, `parent`; Express JWT bearer token; bcrypt; reset-token table and SMTP password reset for staff; client caches identity/token in localStorage | Shared Laravel `User`, roles/permissions, linked staff/student/parent identity, session guard, password reset | Consolidate with website admins/editors; use server-side policies and secure session lifecycle; do not port localStorage auth as target architecture. |
| Admin | `/admin`, students, parents, teachers, classes, subjects, results/entry, sessions, terms, users, settings | `School`/`People`, `AcademicStructure`, `Results`, `Identity` domains | Preserve CRUD, status, assignments, settings and result dashboard capabilities; replace static sample “Recent Activities” with real audit/activity records. |
| Teachers | Dashboard, assigned subjects, results list/entry, profile | `TeacherProfile`, assignments, results policies | Preserve scope to assigned classes/subjects; check authorization server-side on every listing, score mutation and report action. |
| Students | Dashboard, profile, result selection/display | Student profile/enrolment + published-result read policy | Preserve own-profile/own-result access; never rely on React route/menu visibility. |
| Parents | Dashboard and result page | Parent profile + `ParentStudent` many-to-many pivot + child-scoped policy | Source schema links each `parents` row to one student; model target explicitly for multiple children per parent. |
| Academic structure | Academic sessions; terms per session; classes; arms; class-arms; class teacher | `AcademicSession`, `Term`, `SchoolClass`, `ClassArm`, `TeacherAssignment` | Normalize array fields to assignment/pivot tables; retain active/current session/term invariants. |
| Students/teachers/parents/users | Student IDs, profile/contact/status/photo; teacher IDs and class/subject arrays; one parent record per child; login account attached by nullable foreign keys | People records and explicit user/profile linkages | Existing schema is source for field inventory, not an immutable target. Define uniqueness, lifecycle and household links cleanly. |
| Subjects | Code/name/class/optional arm/teacher/status | `Subject`, class/arm scope, teacher-subject assignment | Preserve class-specific and all-arms behavior; normalize teacher and class assignments. |
| Score entry | CA1, CA2, CA3, exam; configurable maxima; per-student offered toggle; batch UI; grade/remark on save; pending/publish | `AssessmentScheme`, `AssessmentComponent`, `Score`, publication workflow | Preserve configured max points and not-offered semantics. Server validates boundaries; use transactions and audit changes. |
| Grade rules | Default bands A–F; admin settings UI validates whole-number 0–100 full coverage with no overlap/gaps | `GradeScale`, `GradeBand` | Seed exact current bands and retain configurable complete coverage. Do not silently alter school rules. |
| Term evaluations | Affective/psychomotor traits rated 1–5; teacher/principal term remarks | `AffectiveTrait`, `AffectiveRating`, `TermRemark` | Preserve fields, period uniqueness and rating range. |
| Result calculations/report card | Generated total score; grade; pass percentage; subject ranks; class/arm ranks; averages/high/low; pass/fail counts; graph; printable card; third-term cumulative term totals/averages | Result calculation/service + report query/view/PDF/print rendering | Preserve current third-term rule: include saved published First/Second/Third results only, average only existing terms; current ranks use competition positions (`count(higher)+1`), ties share position. Define authoritative server-side calculation. |
| Publishing | Result status `Draft`, `Pending`, `Published`; result viewing filters published | Publication workflow and policy | Preserve state transitions and ensure no draft/pending leaks to student/parent/public checker. |
| Token result checker | Optional mode `portal`, `token`, `both`; admin-generated 12-character unambiguous tokens; surname+token check; per-IP in-memory failed-attempt limiter; token usage count; published periods | `ResultAccessSetting`, hashed/safely stored token credentials, throttled public endpoints | Preserve optional access feature if desired; ensure enumeration resistance/shared limiter, audit/regeneration and backend result publication checks. |
| Uploads | 2MB PNG/JPEG/GIF/WEBP signature-checked uploads; PostgreSQL `uploaded_files`; paths constrained to school logos/student photos; public image serving | Shared `MediaAsset` and secure storage adapter | Merge with website media library; classify ownership/access; retain MIME/signature/size/path controls; avoid serving private documents publicly. |
| School settings | Identity/contact/logo, current session/term, pass %, assessment maxima, grade highlighting, result access mode | Shared branding/settings plus results-specific settings | Split website identity/theme from academic/result configuration while providing one admin surface and unified settings service. |

### Results schema inventory

Source tables: `school_settings`, `academic_sessions`, `terms`, `teachers`, `classes`, `arms`, `class_arms`, `subjects`, `students`, `parents`, `app_users`, `grade_bands`, `affective_traits`, `results`, `affective_ratings`, `term_remarks`, `uploaded_files`, `student_tokens`, `password_reset_tokens`. PostgreSQL includes FKs/indexes, unique score-per-student/subject/session/term, generated `total_score`, and default grade/trait seeds.

### Results source gaps/risk items to resolve in Laravel

- `Parents` is structurally one child per parent record; a proper family account should support many linked students.
- Teacher scoping is not consistently enforced by the API: generic reads of students/classes/subjects are broader than assignments, and the staff report-bundle endpoint accepts any teacher for any student. Make policies constrain the selected class, subject and student server-side.
- The generic `/data/:table` endpoint has table/column allowlists and parameterized filter values, but exposes broad CRUD semantics and uneven resource-specific validation. Replace with typed Laravel resources, FormRequests and policies rather than porting it literally.
- Student/parent identity scoping in generic data filters uses overloaded field assumptions for `students` versus result `student_id`; verify/repair during data mapping. Keep explicit internal UUID foreign keys separate from human-facing student numbers.
- JWT has a development fallback secret in source, client stores bearer token in localStorage, and login has no visible shared/distributed throttling; do not carry these defaults to target.
- Score status and computed grade are client-submitted through generic APIs; target recalculates/validates server-side and audits state transitions.
- Result report rank/statistics currently derive from published results scoped to the student's class+arm for the report bundle; clarify school policies before changing tie handling or ranking populations.
- No payment/family fee modules exist in either repository. Payment gateways, fee assignments, transaction verification, receipts and webhooks are entirely new target domains.

## 5. Shared target module map

| Laravel module/domain | Owns | Principal entities / boundaries |
|---|---|---|
| `IdentityAccess` | Unified login, password recovery, roles/permissions/policies, session revocation, admin 2FA hook | users, roles, permissions, role-user, profile links, sessions/reset credentials |
| `Website` | Public routes, CMS, SEO, theme tokens, navigation/footer and public presentation | page/typed blocks, homepage sections/slides, settings, history, programme/curriculum, leadership, news, events, gallery, FAQ, admissions, announcements, portal links |
| `Media` | Upload validation, library/search/reuse, metadata and storage abstraction | media assets, variants, polymorphic media attachments; initial assets imported from both repos |
| `SchoolManagement` | Student/staff/family records and academic structure | students, teachers, parents, parent-student pivots, classes/arms, subjects, assignments, sessions/terms |
| `Results` | Assessment schemes, scores, grades, publication, result viewing/printing | assessment components, grade scales/bands, scores/results, remarks, affective ratings, report policies |
| `Payments` | Fee assignment, checkout orchestration, gateway drivers, callbacks/webhooks, receipts/refunds | fee schedules/charges, invoices/obligations, payment attempts/transactions, gateway settings, webhook receipts/idempotency keys, receipts |
| `Notifications` | Contact/admission/transaction/result notifications | notification templates, delivery jobs/records; provider boundary |
| `Audit` | Append-only sensitive action trail and protected viewer | actor/action/resource/metadata/IP/time; secrets and credentials excluded |
| `IntegrationApi` | Versioned external endpoints and provider callback adapters | API auth/rate limits, webhook signatures, adapter interfaces |

**Suggested Laravel structural rule:** organize by domain under `app/Domain/<Domain>/` (actions/services, models, policies, requests/resources as appropriate), with standard Laravel HTTP adapters (`routes/web.php`, `routes/api.php`, controllers) kept thin. Use framework migrations and one shared Postgres database. Public page views and admin/portal interfaces are separate presentation shells, not separate applications.

## 6. New payment subsystem (no source-system migration)

```text
Student/Parent -> fee obligation -> choose enabled provider
  -> PaymentGateway contract -> Paystack / Flutterwave / Moniepoint adapter
  -> pending payment attempt + unique reference/idempotency key
  -> provider checkout -> signed webhook + server-side verification
  -> atomic idempotent successful transition -> receipt + audit + notification
```

Credentials are encrypted server-side or injected by environment; never included in public/admin HTML or logs. Gateways implement the same initiation/verification/refund contract; each provider has signature verification and documented environment/currency capabilities. Persist only safe transaction metadata, amount/currency/reference/provider/state/verification timestamps, no PAN/CVV. Fee assignment supports session/term/class/student/fee-type/due-date overrides. Results access is not payment-gated unless an explicit school setting enables the rule. Do not claim live gateway functionality until credentials, provider contracts and webhook verification can be configured/tested.

## 7. Migration/implementation order suggested by source dependencies

1. Establish new private `emmaculate-full-website` Laravel repository; preserve source repos unchanged; choose supported Laravel/PHP version and environment configuration.
2. Build shared schema, domain boundaries, seed/bootstrap settings, admin identity and policies; decide parent-child model and result isolation before importing records.
3. Import the eight school JPEGs into media storage and seed factual CMS data from `content/*.ts`; preserve absent/placeholder official facts as absent or clearly unconfirmed.
4. Port public pages/components faithfully, routing CMS-backed content into the original visual patterns; verify responsive, keyboard, reduced-motion and image behavior.
5. Port school structure/results and role portals, server-side calculation/publication/report rendering; add data/import verification if source database exports are supplied.
6. Add complete CMS/media/contact workflow; then payment interfaces and provider adapters/webhooks/receipts; audit, notification and security hardening.
7. Test public rendering, all role/resource boundaries, score formulas/ties/third-term rules, CMS lifecycle, media validation, gateway signature/idempotency, and responsive visual parity.

## 8. Explicit no-assumption / follow-up data boundaries

- Source code includes school statements and image assets listed above; it does not include production database exports, user lists, student records or payment credentials.
- School phone, email, precise street address, office hours, social URLs and some official biographies/curriculum/admission details are marked unconfirmed in website content. Keep those placeholders/unset rather than inventing values.
- No real payment gateway, public portal, application persistence, event records, FAQ records, news records, media-upload workflow, or production credentials are present in source snapshots.
- Maintain backward-compatible import paths or explicit import scripts for actual data only when exports/authorized database access are available; never assume repository clone contains live DB data.
