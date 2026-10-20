# Update 9 (includes everything from Update 8)

Update 8 (automatic Student ID, bulk PIN slips, student form rules, teacher/parent passwords, one portals page, mobile menu fade) is bundled in this package: files that Update 8 already put on the site are simply overwritten with the same or newer content. See `docs/CHANGES_UPDATE_8.md`.

## New in Update 9
1. **Graduation and archive (backend only).** Mark the last class (e.g. SS3) as a **Final class** (Classes > edit). When the session is processed under *Promote & graduate*, eligible students become **Graduated**, move to **Students & staff > Graduated students (archive)**, and can no longer sign in or check results. Students below the standard wait for the administrator, who can choose Promote, Retain or **Graduate**. Parents no longer see a graduated child's results, but can still see and pay any unpaid fees. Only administrators open their results (Results > Reports archive, with a "Graduated" mark). Students who left outside the process can be moved with *Graduate and archive* on the Students list; a mistake is undone with *Restore to active*.
2. **Session and Term on the public Check Result page.** Visitors pick a session and term (only ones with published results are listed; the current term is pre-selected) and can check past terms as well as the current one. Unpublished terms are never available.
3. **A simpler admin menu.** Regrouped into: Students & staff, Results, Fees & payments, Classes & subjects, Admissions & messages, Website, Homepage & menus, Settings and Advanced (rarely used raw records). Less-used groups start folded. The dashboard has **Start here** buttons (Add a student, Print PIN slips, Results, Reports archive, School fees, Post news, School profile, Portal switches), shown only for what the person may do.
4. **Cloudflare Turnstile on every sign-in** (admin, staff, parent, student, Forgot password, Check Result). Off until `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are set in `.env`. Setup: `docs/SECURITY.md`. The security policy now allows the Cloudflare check to load.
5. **Heavy-traffic readiness.** New database indexes (results by term/status, fees, payments, activity log and more), PHP OPcache and Apache limits tuned for small servers in the Docker image, compression and long caching, `/up` health check, and `docs/PERFORMANCE.md` with the host settings to apply.
6. **Whole-school upload sheet.** Students > **Download upload sheet** makes an editable spreadsheet with empty lines for every class and arm (class and arm already filled in; choose classes and lines per arm). Fill in names, save as CSV, **Import from CSV**. Empty lines are ignored; Student IDs are created automatically; up to 2,000 students per file.
7. **Works on cPanel, XAMPP and other platforms.** README rewritten with a platform table; new `docs/INSTALL_XAMPP.md`; cPanel guide now uses a GitHub button that builds the upload ZIP (**Actions > Build cPanel release**) and explains a no-Terminal route; XAMPP needs PHP 8.3+.
8. **Payments reviewed.** The payment flow (server-side verification of amount, currency and reference, signature-checked idempotent webhooks, receipts) was read end to end and looks correct. Two fixes: student payers now give the gateway a real email (a linked parent's, else the school's) instead of the internal placeholder address; and parents keep seeing fees and payment history of graduated children.
9. **No more school names in the code.** Page descriptions, the leadership page and internal student account addresses use the school's own settings.

## Files
See the ZIP: every file listed is new or replaced. New migrations: `2026_10_18_000001_add_student_id_prefix_and_counters`, `2026_10_19_000001_add_graduation_support`, `2026_10_19_000002_add_performance_indexes` (all additive; nothing is dropped).

## After deploying
1. Run the new GitHub test workflow and check all three database jobs are green.
2. School settings: set the **Student ID prefix**; Students list: **Assign Student IDs to older records**.
3. Classes: switch **Final class** on for SS3.
4. Add Turnstile keys (docs/SECURITY.md), then try signing in to /admin, /login and Check Result.
5. On Render set the health check path to `/up`; consider a paid instance before results day (docs/PERFORMANCE.md).
6. Reprint PIN slips (People > PIN slips) and switch the PIN on in Portal settings when ready.

## Not yet verified
Written without being able to run PHP. Please try on the live site: the Turnstile widget on each form, the PIN-slips and upload-sheet downloads, the graduation steps with a test class, and a payment in the gateway's test mode.
