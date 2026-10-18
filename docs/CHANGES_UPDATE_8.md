# Update 8 — automatic Student ID, bulk PIN slips, student form rules, teacher/parent passwords

## What changed
1. **Student ID replaces the admission number everywhere** (sign-in, Check Result page, student form, lists, PIN slips, printed report cards, CSV import). The ID is created **automatically** the moment a student record is created (typed, imported, or created from a user account) and is **never typed in or repeated**.
   - Format: `PREFIX/SESSION/NUMBER`, e.g. **EAC/2627/0001**. 2627 is the short form of the session 2026/2027; 0001 is the student's running number within that session (a new session starts again at 0001).
   - **The prefix is editable**: Admin console > School settings > *Student ID prefix* (default EAC). Changing it only affects students added afterwards, so the site can be reused by another school.
   - Concurrency-safe: a locked counter per prefix and session hands out each number once; if an older record already holds a number, the next free one is used.
   - The school's *Admissions* (applications) pages are unchanged; only the student identifier was renamed.
2. **Older numbers**: Students list > **Assign Student IDs to older records** converts every student still carrying an old-style number to the new format (oldest first). Reprint PIN slips afterwards.
3. **Bulk PIN slips**: Admin console > People > **PIN slips** (also a *Print PIN slips* button on the Students list). Choose one class (optionally one arm) or the whole school (one PDF per class inside a ZIP). Choose *only students with no PIN yet* (earlier slips stay valid) or *every active student* (replaces all PINs). Selecting rows on the Students list and *Issue PIN slips* still works.
4. **Creating a student**: first name, last name, gender, date of birth, class, arm (when the class has arms) and date admitted are all required before the profile can be saved. The Student ID is assigned on save (shown read-only). **Parent / guardian** can be linked from the form (parents only, shown by name); it is optional and can be done or changed later when editing the student. The old "student portal account" picker is gone.
5. **Teacher and parent profiles** now require an **email and a password** when created; the sign-in account is created from them. Admin can set a new password when editing (leave blank to keep it); the email is kept in step with the sign-in. Passwords need 12+ characters with upper and lower case, a number and a symbol.
6. **Teachers and parents change their own password**: the existing *Forgot password* email link, plus a new **Change password** button on the portal dashboard (current password + new password). Students do not get one (they sign in with Student ID, surname and PIN).
7. **CSV import**: `student_id` is now optional and used only to *update* an existing student; leave it blank for new students (an ID is assigned). `first_name` and `last_name` are the only required columns.

## Files
New: `database/migrations/2026_10_18_000001_add_student_id_prefix_and_counters.php`, `app/Models/StudentIdCounter.php`, `app/Domain/Identity/StudentIdGenerator.php`, `app/Domain/Identity/AccountPasswords.php`, `app/Domain/Results/Services/PinSlipService.php`, `app/Filament/Pages/StudentPinSlips.php`, `app/Filament/Concerns/HandlesAccountPassword.php`, `app/Http/Controllers/AccountPasswordController.php`, `resources/views/portal/password.blade.php`, `tests/Feature/StudentIdTest.php`, `tests/Feature/BulkPinSlipsTest.php`, `tests/Feature/AccountPasswordTest.php`, `docs/CHANGES_UPDATE_8.md`
Changed: `app/Models/Student.php`, `app/Models/SchoolSettings.php`, `app/Domain/Identity/IdentitySync.php`, `app/Domain/Auth/Support/PortalAccess.php`, `app/Domain/Results/Services/StudentLookup.php`, `app/Domain/Results/Services/StudentCsvImporter.php`, `app/Http/Controllers/StudentAccessController.php`, `app/Http/Controllers/ResultCheckController.php`, `app/Providers/AppServiceProvider.php`, `app/Filament/Pages/PortalSettings.php`, `app/Filament/Resources/SchoolSettingsResource.php`, `app/Filament/Resources/StudentResource.php`, `app/Filament/Resources/StudentResource/Pages/ListStudents.php`, `app/Filament/Resources/ParentProfileResource.php`, `app/Filament/Resources/ParentProfileResource/Pages/CreateParentProfile.php`, `.../EditParentProfile.php`, `app/Filament/Resources/TeacherResource.php`, `app/Filament/Resources/TeacherResource/Pages/CreateTeacher.php`, `.../EditTeacher.php`, `resources/views/auth/student-login.blade.php`, `resources/views/auth/portal-off.blade.php`, `resources/views/site/check-result.blade.php`, `resources/views/portal/pin-slips.blade.php`, `resources/views/portal/report.blade.php`, `resources/views/portal/dashboard.blade.php`, `resources/docs/students-import-sample.csv`, `routes/web.php`, tests `StudentAccessTest`, `StudentPinTest`, `PortalSwitchesTest`, `PeopleSyncTest`, `StudentCsvImporterTest`, `ReportCardTest`

## Apply order
Needs Update 7C on main (student PINs, PIN slips view).

## Database
One additive migration: `school_settings.student_id_prefix` (default EAC) and a `student_id_counters` table. The database column that stores the ID is still called `student_number`; only the labels and sign-in form field (`student_id`) changed. Nothing is dropped.

## After deploying
1. School settings: check the **Student ID prefix** (EAC).
2. Students list: **Assign Student IDs to older records** (existing students get EAC/2627/0001 style IDs).
3. People > **PIN slips**: reprint slips for the classes (old slips show old numbers).
4. Tell students to sign in with the Student ID (EAC/2627/0001).

## Not yet verified
Written without being able to run PHP. The Filament download buttons (PIN slips page, ZIP for the whole school) and the Student/Teacher/Parent forms should be tried once on the live site.
