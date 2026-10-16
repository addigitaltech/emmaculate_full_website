# Update 6 — portal switches that really remove portals, one login page per portal, automatic people sync
Apply AFTER Update 5. Several files here also contain Update 4/5 content (routes/web.php, AppServiceProvider.php, SchoolSettings.php, SiteController.php, AuthController.php, footer partial): MERGE, keep your earlier fixes (including the newer CSP/security fixes already in the repo), do not duplicate.

## 1. Portals fully removed when switched off
- New switches in Admin > System > Portal & website switches (Super Admin): Admission portal, Staff portal, Student portal, Parent portal, Results portal ("Check Result"). New columns staff_portal_enabled and admission_portal_enabled.
- The public portal list now comes from the switches (PortalDirectory), not from the old Portal links records. A switched-off portal disappears at once from: the Portals menu, the home page quick bar, the news sidebar, the portals hub page, the sign-in chooser and the footer "Portals" column (that column is now generated automatically).
- Results Portal appears only when "Check Result" is on, and links straight to /check-result (no login form).
- Admission portal off hides the Apply online form and rejects submissions (404); the Admissions information page stays.
- Student/parent/teacher sessions are ended and sign-in refused when their portal is off. Administrators and results staff are never locked out; /admin always works.

## 2. One sign-in page per portal
- /student/login: admission number + surname only. /parent/login: email + password + forgot password. /staff/login: email + password for teachers and staff. /login is now only a chooser listing the portals that are switched on. A switched-off portal's page shows a notice instead of a form.
- A parent cannot sign in on the staff page and staff cannot sign in on the parent page (friendly message). Old POST /login still works for compatibility. The student form now posts to /student/login (route name unchanged: student.login).

## 3. Users, students, teachers and parents stay in sync (App\Domain\Identity\IdentitySync)
- Every student added in the admin, uploaded from CSV or created any other way gets a login account (role Student) automatically; renaming the student renames the account.
- A teacher or parent with an email gets an account with the right role (an existing account with that email is linked instead).
- A user created or edited in Admin > People > Users with role Teacher, Parent or Student automatically gets the matching record: Teacher (so subjects can be assigned under Who teaches what), Parent, or Student (admission number STU-xxxxx until completed in People > Students).
- `php artisan school:sync-people` backfills everything that already exists; it also runs inside `school:init` on every deploy. On the live test database 5 of 6 students had no account before this.

## Tests
PortalSwitchesTest, PeopleSyncTest (new); PortalLinkStatusTest reduced to the still-valid link validation (the public list no longer comes from Portal links).

## Demo content already inserted into the live test database (not part of this package)
News (4), events (3), FAQs (5 + the owner's own), announcements (1 published, 1 hidden draft), an "Events" gallery album with 3 photos, plus a repair of an announcement whose status had been typed as "publishe". All titles start with "Demo". Delete them in the admin before launch.
