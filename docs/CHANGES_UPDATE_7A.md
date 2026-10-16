# Update 7A — safer results engine and automatic tests

## What changed
1. **Automatic tests on three databases** — `.github/workflows/tests.yml` runs the full test suite on SQLite, MySQL and PostgreSQL on every push (and from the Actions tab via "Run workflow").
2. **One set of rules for results** — the older single-result routes (`results.store`, `results.publish`, `results.report`) no longer have their own logic. Saving and publishing go through `ResultSheetService`; the old report link redirects to the one report page.
3. **Publishing recomputes** — before a class (or single result) is published, every total, grade and remark is recomputed from the stored component scores using the *current* score split and grade bands. If a stored score no longer fits the current maxima, nothing is published and the screen lists the lines to correct.
4. **Positions** — class, arm and subject positions on the report card now rank among *every* entered result of the class and term (draft, pending or published). Averages are rounded to 2 decimals before ranking, so ties are exact. A student's own subject lines are fetched directly, so a student moved to another class mid-term keeps every subject on the report.
5. **New tests** — `tests/Feature/ResultIntegrityTest.php` proves: positions include unpublished results; one student's saves never change another student's results; ties share a position; publishing recomputes stale grades; publishing is blocked for out-of-range scores; moved students keep their subjects.

## Files
New: `.github/workflows/tests.yml`, `tests/Feature/ResultIntegrityTest.php`, `docs/CHANGES_UPDATE_7A.md`
Changed: `app/Domain/Results/Services/TermReportBuilder.php`, `app/Domain/Results/Services/ResultSheetService.php`, `app/Http/Controllers/ResultsController.php`, `tests/Feature/StaffResultsTest.php`

## Not yet verified
These changes were written without being able to run PHP. The new GitHub Actions run is the first real test: open the repository's **Actions** tab after pushing and check all three jobs (sqlite, mysql, pgsql) are green. Send me any red job's error text and I will fix it.

## Left for later (unused, safe to ignore for now)
`ResultReportBuilder`, `resources/views/portal/report-card.blade.php` and `resources/views/portal/score-entry.blade.php` are no longer reached by any screen and can be deleted in a later cleanup.
