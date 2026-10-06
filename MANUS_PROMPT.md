Task: apply "Update 2" (staff results workflow, report card, dashboards, admin dashboard, student import) to the existing Laravel repo addigitaltech/emmaculate_full_website. Do NOT rebuild or restructure anything. Read CHANGES.md in the attached folder `emmaculate-update-2` first.

1. Copy every file from the folder into the repo root (same relative paths, overwrite).
2. These files were written without a PHP runtime, so they have never been executed. Run and fix anything that fails:
   - composer install; php -l on every new/changed .php file
   - php artisan migrate:fresh --seed (twice is fine); php artisan view:cache; php artisan route:list
   - php artisan test  (all existing tests plus the new StaffResultsTest and StudentCsvImporterTest must pass; if an old test fails because of intended text changes, update the test and tell me)
   - npm ci && npm run build
3. Then do a real walk-through with seeded demo data (do not commit demo data): create a session + current term, a class with an arm, two subjects, a teacher with one assignment, a student, a parent linked to the student.
   - As the teacher: open /portal/staff/results, the score sheet and the student sheet; save scores; confirm a class the teacher is NOT assigned to returns 403 (also by editing the URL).
   - As a results admin: publish the class; confirm the parent now sees the report at /portal/reports/{student}/{term}, an unlinked parent gets 403, and before publishing the parent got 404.
   - Download the report as PDF (?download=pdf) and confirm it renders (logo, table, summary, bars).
   - In /admin: dashboard widgets load, Students > Import from CSV works with resources/docs/students-import-sample.csv (create class JSS1 with arms R and S first), student filters work.
   - Check /login, /forgot-password and the footer "Admin login" link.
4. Do not change roles/permissions, payments, or the public-site design. If you must change a file to fix an error, keep behaviour the same and tell me exactly what you changed.
5. Commit to main as "Update 2: staff results workflow, reports, dashboards, admin" and push (Render redeploys automatically).
6. Report: every file you changed beyond the package, every error fixed, and the result of each check above.
