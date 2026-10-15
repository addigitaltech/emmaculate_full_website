# Student Promotion Implementation Plan

> **For agentic workers:** Implement the approved tasks incrementally and test each vertical slice. The user has authorized implementation and selected an administrator-launched batch for Automatic mode.

**Goal:** Provide auditable manual or automatic promotion decisions after third-term results, preserving the historic class and result records.

**Architecture:** Add school-configured promotion mode and thresholds, plus a per-class next-class mapping. A promotion service evaluates published first-, second- and third-term scores using the existing cumulative calculator and writes one durable transition record per student/source-session/destination-session. An authorized admin page launches a selected session transition; Automatic mode applies eligible configured class paths, while incomplete, ineligible, or unmappable students remain in review. Manual mode presents the same review data and permits explicit Promote or Retain decisions.

**Tech Stack:** Laravel 12, PHP 8.3, Eloquent, Filament 3, Livewire, PHPUnit/Pest project test runner.

**Spec:** User attachment `pasted_content.txt`, §8, plus the user's clarification on 2026-10-15: use third-term cumulative average and subject pass count; expose Automatic/Manual mode in Portal Settings; the admin launches a chosen session transition.

## Global Constraints

- Keep the existing Laravel application and role/permission structure; do not replace the results portal.
- Default promotion mode to Manual and do not hard-code average or pass-count thresholds.
- Use the existing school pass percentage to decide whether a subject is passed.
- Use saved, published results only; incomplete or unpublished records must not trigger automatic promotion.
- Never update or delete historic result rows; student class changes affect only the current student profile and are recorded in the promotion ledger.
- Do not run production migrations, deploy, commit, or push without the user's separate approval.
- Results Admins may manage promotion settings, but portal and website access switches remain Super Admin-only.

## Review Focus

- A missing term/subject row or an unpublished result must result in review, not automatic promotion.
- A class with no configured next class, or an arm without a destination match, must remain unchanged and be flagged for review.
- Re-running a source→destination transition must not promote or create a duplicate decision for the same student.
- Automatic mode must be rejected until both the minimum cumulative average and minimum number of passed subjects are configured.
- Manual Retain must preserve the recorded source class/arm; manual Promote must validate that the selected arm belongs to the destination class.

---

### Task 1: Promotion settings and class progression

**Files:**
- Create: `database/migrations/2026_10_15_000001_add_student_promotion_support.php`
- Modify: `app/Models/SchoolSettings.php`
- Modify: `app/Models/SchoolClass.php`
- Modify: `app/Filament/Pages/PortalSettings.php`
- Modify: `app/Filament/Resources/SchoolClassResource.php`
- Test: `tests/Feature/PromotionSettingsTest.php`

**Interfaces:**
- Settings: `promotion_mode` (`manual` or `automatic`), nullable `promotion_min_average`, nullable `promotion_min_subjects_passed`.
- Class progression: nullable `school_classes.next_class_id`, configured by the authorized academic-structure administrator.
- Automatic mode requires both thresholds; new and existing school records default to Manual.

- [x] **Step 1: Write settings and progression tests**
  - Assert a fresh school setting loads Manual mode and null thresholds.
  - Assert automatic mode cannot be saved unless both thresholds are valid.
  - Assert valid Manual/Automatic settings can be saved and a class can select a distinct next class.
- [x] **Step 2: Run the focused tests and confirm they fail for missing columns/behavior**
  - Run: `php artisan test --filter=PromotionSettingsTest --no-ansi`
- [x] **Step 3: Add the additive migration, model validation/casts, and Portal Settings controls**
  - Add the self-referencing next-class relation and select to the class editor; exclude the current class from its own next-class options.
  - Keep portal and website access switch controls Super Admin-only; expose only promotion settings to Results Admins.
- [x] **Step 4: Run the focused tests**
  - Run: `php artisan test --filter=PromotionSettingsTest --no-ansi`

### Task 2: Cumulative eligibility and durable transition ledger

**Files:**
- Create: `app/Models/StudentPromotion.php`
- Create: `app/Domain/Results/Services/PromotionService.php`
- Modify: `database/migrations/2026_10_15_000001_add_student_promotion_support.php`
- Test: `tests/Feature/PromotionServiceTest.php`

**Interfaces:**
- `PromotionService::processTransition(AcademicSession $source, AcademicSession $destination, User $actor): Collection`
- `PromotionService::decide(StudentPromotion $promotion, User $actor, string $decision, ?int $destinationClassId = null, ?int $destinationArmId = null): StudentPromotion`
- Ledger uniqueness: one row per `student_id + source_session_id + destination_session_id`; preserve source/destination class and arm, metrics, mode, reason, decision actor, and timestamps.

- [x] **Step 1: Add tests for eligibility, incompleteness, arm mapping, and idempotency**
  - Build per-subject cumulative averages from published scores through sequence 3 using `ResultCalculator::cumulativeAverage()`.
  - Overall promotion average is the arithmetic mean of offered subjects' cumulative averages; a subject passes at the existing school `pass_percentage`; require both configured thresholds.
  - Assert missing/pending/unpublished term results and absent progression mappings create Review records and do not change the student.
  - Assert same-name arms map across the configured class progression; absent matches create Review.
  - Assert repeat batch launch creates no duplicate transition or second class change.
- [x] **Step 2: Run the focused service tests and confirm they fail**
  - Run: `php artisan test --filter=PromotionServiceTest --no-ansi`
- [x] **Step 3: Implement the ledger and service**
  - Snapshot the exact thresholds/pass mark and cumulative metrics used for each decision.
  - Automatic mode promotes only eligible students with a valid next-class/arm mapping; other students are marked Review.
  - Manual mode creates reviewable records without changing student assignments. Promote validates the destination class/arm; Retain records the source assignment.
  - Record every batch and manual decision through the existing `AuditLog`.
- [x] **Step 4: Run the focused service tests**
  - Run: `php artisan test --filter=PromotionServiceTest --no-ansi`

### Task 3: Authorized session-transition management

**Files:**
- Create: `app/Filament/Pages/StudentPromotions.php`
- Create: `resources/views/filament/pages/student-promotions.blade.php`
- Test: `tests/Feature/StudentPromotionManagementTest.php`

**Interfaces:**
- Admin page selects source session and next session, then launches the selected transition.
- Page is available only to existing school administrators with the relevant student/results/academic-structure permission; teachers and family roles cannot launch or decide promotions.
- Manual mode lists pending decisions with explicit Promote and Retain actions. Automatic mode applies the configured rule when an authorized administrator launches the batch; students needing review remain unchanged.
- The page is registered through the existing Filament page auto-discovery; no panel-provider change is required.

- [x] **Step 1: Write authorization and workflow tests**
  - Assert an authorized school admin can open the page, launch a valid source→destination transition, and act on Review records.
  - Assert a teacher cannot open the page or invoke a decision endpoint.
  - Assert session selection rejects same-session transitions and requires a third term.
- [x] **Step 2: Run the focused workflow tests and confirm they fail**
  - Run: `php artisan test --filter=StudentPromotionManagementTest --no-ansi`
- [x] **Step 3: Implement the Filament management page**
  - Display status, student, original class/arm, cumulative average, subjects passed, and review reason.
  - Provide batch launch and per-student Promote/Retain actions; retain existing portal and family report routes unchanged.
- [x] **Step 4: Run the focused workflow tests**
  - Run: `php artisan test --filter=StudentPromotionManagementTest --no-ansi`

### Task 4: Regression verification

**Files:**
- Test: `tests/Feature/PromotionSettingsTest.php`, `tests/Feature/PromotionServiceTest.php`, `tests/Feature/StudentPromotionManagementTest.php`

- [x] Run: `php artisan test --no-ansi` — 86 tests and 353 assertions pass in the isolated test copy.
- [x] Verify the existing suite plus promotion tests pass; confirmed the historic third-term report retains its original class/arm and published result rows after promotion.
- [x] Check `git diff --check` and confirm all changed files are within the Results Portal/promotion scope.
- [x] Present the final changed-file and test summary. No commit, push, production migration, or deployment was performed.
