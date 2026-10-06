# Emmaculate Academy — Update 2: admin, staff, student, parent and results portals

Copy every file into the project root (same paths), commit, push to `main`. No file is deleted. Includes the earlier "fix 1.1" if it was not applied yet (AppServiceProvider and DesignRefreshSeeder here are the latest versions of those files).

## What is new
**Footer**
- "Admin login" link in the footer of every public page (goes to /admin).

**Staff results workflow (teachers, results admins, super admin)** — new area at /portal/staff/results, modelled on the school's reference system (PDF)
- Pick session/term, then a class and arm -> student list with search, result-status pills, **Enter result** and **Print result** per student.
- **Student result sheet**: every subject with offered tick, CA1/CA2/CA3/Exam, live total and grade, behaviour ratings (1-5), class-teacher remark and (admin only) principal remark.
- **Subject score sheet** for teachers: a whole class for one assigned subject, four score columns, live totals and grades. Blank lines are ignored.
- **Publish / unpublish** a class's results (results admin only; unpublish needs a written reason; both are audit-logged).
- **Reports archive**: search by student name or ID, session and term.
- All writes go through one service (`ResultSheetService`): teachers only for classes/subjects assigned to them, only the current term, never over a published line, maxima and grade bands enforced on the server.
- Old one-student-at-a-time entry screen now redirects to the new area.

**Report card** (`/portal/reports/{student}/{term}`) — table-based so it prints and exports to PDF correctly
- Subject table with CA columns (hidden when a component is 0), total, class average, highest, lowest, subject position, grade, remark.
- Result summary (students in class, class position, arm position, total, average, subjects offered/passed/failed), class-performance bars, behaviour ratings, remarks, grading key, signature lines.
- Staff may preview pending results (clearly marked); students and parents only ever see **published** results, only their own / linked children. Honours the existing "gate results behind fees" setting.

**Dashboards**
- Student: profile + list of published report cards. Parent: each linked child with report cards. Teacher: assigned classes/subjects + score sheets. Admin: pending-results count and shortcuts. Fee/payment sections are unchanged.

**Admin console (Filament)**
- Branded with the school logo/favicon and colours; dashboard now has the counts from the reference system (students, teachers, classes, arms, subjects, results awaiting publication) plus teacher and class tables.
- Sidebar shortcuts: Manage results, Reports archive, View website.
- Students: **Import from CSV** (sample: `resources/docs/students-import-sample.csv`), class/arm/status filters, Enter/Print result buttons.
- Behaviour traits (Attendance, Attentiveness, Honesty, Industriousness, Initiative, Neatness, Relationship with others, Handwriting, Punctuality) are seeded once; editable under Results.

**Fixes**: /login and password pages no longer error (layout data supplied); logo/favicon/description filled when empty; WhatsApp button hidden on portal pages; home announcements link fixed.

## Settings to confirm in the admin (School settings)
- Score split. Default is **CA 40 + Exam 60**. The PDF sample uses CA1/CA2/CA3 = 10 each + Exam 70: set 10 / 10 / 10 / 70 if that is your format. Sheets and report adapt automatically.
- Pass percentage (default 40; the PDF sample shows 50).
- Create the academic session + terms (Results > Academic sessions / terms) before staff can enter results; classes, arms, subjects and teacher assignments must exist too.

## Tests added
`tests/Feature/StaffResultsTest.php`, `StudentCsvImporterTest.php`; `ExampleTest.php` updated for the new home page text.
