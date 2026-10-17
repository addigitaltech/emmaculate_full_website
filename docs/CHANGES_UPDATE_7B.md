# Update 7B — class teachers per arm

## What changed
1. **Class teacher per arm** — new table `class_teachers` (one class teacher per class arm; one teacher may be class teacher of several arms or classes; a class without arms has one class teacher).
2. **Admin screen** — Admin console > Academics setup > **Class teachers** (needs the existing "manage teachers" permission).
3. **Behaviour ratings and the class teacher's remark** can now be saved only by an administrator or that arm's class teacher. This is enforced on the server: anything a subject teacher sends for those fields is ignored. The fields are also hidden from subject teachers on the student sheet.
4. **Class teachers can open the student sheet and class list** for their arm even if they teach no subject there.
5. The principal's remark stays administrator-only.

## Files
New: `database/migrations/2026_10_16_000001_create_class_teachers_table.php`, `app/Models/ClassTeacher.php`, `app/Filament/Resources/ClassTeacherResource.php` (+ 3 page files), `tests/Feature/ClassTeacherTest.php`, `docs/CHANGES_UPDATE_7B.md`
Changed: `app/Models/Teacher.php`, `app/Domain/Results/Services/ResultSheetService.php` (also contains the Update 7A changes), `app/Http/Controllers/StaffResultsController.php`, `resources/views/portal/staff/student-sheet.blade.php`

## Apply order
Apply Update 7A first (or build on the update-7a branch). `ResultSheetService.php` here includes the 7A changes.

## Database
Adds one new table, nothing is dropped or changed. The migration runs automatically on deploy.

## After deploying
In the admin console, add each arm's class teacher under Class teachers. Until then only administrators can enter behaviour ratings and class teacher remarks.
