# Emmaculate Academy — Super Admin setup and operations guide

This guide is for the school owner, principal, bursar, administrator and other staff who will operate the production website. It covers the order that settings should be completed, every major function, student CSV import, users and permissions, results, graduation, fees, payments, communications, security and backups.

The application is a single Laravel website with a public school website, administrator area, staff portal, parent portal, student sign-in, results checker, admissions and fee payments.

## 1. Before opening the site to the public

Complete these items in this order:

1. Confirm the cPanel server has PHP 8.3+, MySQL/MariaDB, HTTPS, writable Laravel storage, a cron job and a working SMTP account. Follow `docs/CPANEL_DEPLOYMENT.md`.
2. Create the database and upload the cPanel release. Never upload `.env` from a development computer.
3. Copy `.env.cpanel.example` to `.env` and fill in `APP_URL`, database settings, `APP_KEY`, SMTP settings and the optional payment, Turnstile and SMS settings.
4. Run the first-time commands in the cPanel guide. Create exactly one Super Admin, then remove the temporary `SCHOOL_ADMIN_*` values from `.env`.
5. Visit `/admin`, sign in, and immediately change/verify the administrator contact details and password policy.
6. Work through the setup sequence below before enabling public portals.

Never run `migrate:fresh`, seed demo data, or delete the production database. Production uses `php artisan migrate --force` only.

## 2. Admin area and roles

Open `https://YOUR-DOMAIN/admin`. The main navigation is grouped as follows:

- **Students & staff** — students, graduated students/archive, parents, teachers, users, class teachers and people synchronization.
- **Results** — result administration, published-result management, the staff results entry area, reports archive and report access.
- **Fees & payments** — fee types, fee assignments, payment history/debts, payment events and payment configuration.
- **Classes & subjects** — classes, arms, subjects, academic sessions, terms, assessment configuration, grade bands, affective traits and subject-teacher assignments.
- **Admissions & messages** — admissions settings and applications, contact messages, newsletter subscribers and email center.
- **Website** — school profile, media/pictures, news, events, FAQs, gallery, leadership, programmes and CMS content.
- **Homepage & menus** — hero slides, navigation items, footer sections and homepage content.
- **Settings** — portal switches, promotion settings, security check and other school-wide settings.
- **Advanced** — audit logs and maintenance/security tools available to the Super Admin.

The application enforces permissions on the server. Hiding a menu item is not the security boundary. Use the smallest suitable role:

| Role | Use |
|---|---|
| **Super Admin** | Full setup, users, security, website, academic structure, results, finance and settings. Keep this role to one or two trusted people. |
| **Website Admin** | Website content and public-school presentation. |
| **Content Editor** | News, events, pages, gallery, FAQs and related content. |
| **Results Admin** | Sessions/terms, subjects, marks, publishing, reports and promotion workflows. |
| **Finance Admin** | Fee types, assignments, payments, receipts and payment history. |
| **Teacher** | Assigned classes/arms/subjects and score entry only. |
| **Parent** | Own linked children's portal data and payments. |
| **Student** | Own active student portal data and published results. |

Create normal staff users in **Users** or through the corresponding Teacher/Parent profile. Do not share the Super Admin account.

## 3. School identity and public website

Start with **Website > School profile**:

- School name, short name, address, phone numbers, email and WhatsApp number.
- Motto, description, mission, vision, pledge and anthem.
- Logo and favicon.
- Social links and map/GPS information.
- Theme colours and homepage counters.
- Student ID prefix used for newly generated IDs.

Then configure **Homepage & menus**:

1. Upload pictures in **Website > Media/pictures**. Use JPG, PNG, WEBP or GIF for images and PDF where the field permits it.
2. Create hero slides and choose the uploaded images.
3. Create the main navigation. Use local paths such as `/about`, or safe HTTPS destinations.
4. Create footer sections and useful links.
5. Add programmes, leadership profiles, FAQs, gallery albums/images, news, events and CMS sections/pages.
6. Set items to published/visible only after reviewing them as a guest.

The public site only displays published and visible content. Use the **View website** link in the admin menu after each content change.

## 4. Academic structure — do this before importing students

Complete **Classes & subjects** in this order:

1. **Academic sessions and terms** — create the school year, then its First, Second and Third terms. Mark one session active and one term current. A third term is needed for cumulative promotion/report workflows.
2. **Classes** — create classes such as Nursery 1, Primary 1, JSS1 or SS3. Mark active classes and identify the final class used for graduation.
3. **Arms** — create A, B, Gold, Blue or the school's actual arms and attach them to the relevant classes.
4. **Subjects** — create general and class-specific subjects and mark active subjects.
5. **Assessment and grading** — configure CA 1, CA 2, CA 3 and examination maxima, pass percentage and grade bands. Check that the grade ranges cover 0–100 without unintended gaps or overlaps.
6. **Subject assignments** — use Who teaches what / subject-teacher assignments after teachers and subjects exist.
7. **Class teachers** — assign at most one class teacher per class arm. The class teacher can record behaviour ratings and the principal/class-teacher remark where permitted.

Do not import students into a class or arm that has not been created. If a CSV names an unknown class or arm, that row is rejected.

## 5. People, users and student CSV import

### 5.1 Recommended order

1. Import students or create them manually.
2. Create parents and link their children.
3. Create teachers and assign subjects/classes.
4. Run `php artisan school:sync-people` if the records were imported or edited outside the normal profile forms. The command is safe to run repeatedly and does not intentionally create duplicates.
5. Review **Users** and role assignments before sending login details to families or staff.

### 5.2 Student CSV upload

Use the CSV format in [`CSV_STUDENT_IMPORT_FORMAT.md`](CSV_STUDENT_IMPORT_FORMAT.md) and the example file [`student-import-example.csv`](student-import-example.csv).

Upload from the student import/upload page in the admin area. The importer:

- Requires `first_name` and `last_name`.
- Accepts `date_of_birth` as `YYYY-MM-DD` or `DD/MM/YYYY`.
- Accepts `gender` as Male or Female; other values are left blank/reported.
- Matches `class` and optional `arm` by name.
- Uses a supplied `student_id`/`student_number` to update an existing student; leave it blank for a new student so the system generates the ID.
- Accepts `status` such as `active` or `graduated` according to the form's validation rules.
- Ignores completely blank rows and reports invalid rows without silently creating partial records.

After import, check **Students** for names, class, arm, generated Student ID and status. Run the sync command and confirm the matching Student users exist.

### 5.3 Parents

In **Students & staff > Parents**:

1. Add the parent's full name, unique email and strong password.
2. Link one or more students.
3. Save and test the parent login URL `/parent/login` with the supplied email/password.
4. If a password changes, use the parent's portal **Change password** function or edit it from the admin profile.

A parent can only see published results, fees and payments for linked active children. Graduated children remain available for fee settlement where applicable but are not treated as active portal students.

### 5.4 Teachers

In **Students & staff > Teachers**:

1. Create the teacher with email and strong password.
2. Set the teacher active.
3. Assign subjects/classes/arms under **Who teaches what**.
4. Optionally assign the teacher as class teacher for a class arm.
5. Test `/staff/login` and confirm the teacher can only open assigned score sheets.

### 5.5 Other users

Use **Users** for website, results and finance staff. Set the correct role and never give every employee Super Admin access. After creating a user, test the relevant login and menu visibility.

## 6. Results workflow

Complete this cycle each term:

1. Confirm the current session and term.
2. Confirm assessment maxima and grade bands.
3. Confirm subjects, teachers, classes, arms and class teachers.
4. Enter marks through **Results > Manage results** or the assigned teacher score sheets.
5. Review totals, grades, missing rows, offered/not-offered subjects and behaviour ratings.
6. Publish results only after the administrator has reviewed them. Students/parents and the public checker must never see unpublished results.
7. Use the report link to inspect the printable A4 report card and PDF.
8. Use the reports archive for previous sessions/terms. The cumulative report includes only saved published results through the selected term.

A teacher cannot publish results or edit another teacher's class/subject. A Results Admin or Super Admin can correct and publish according to permissions.

## 7. Promotion and graduation

Before promotion, configure the progression map in **Settings > Promotion settings**:

- Source class to next class.
- Source arm to destination arm where applicable.
- Automatic thresholds such as cumulative average and number of subjects passed, or manual mode.

Run the promotion review only after all required term results are entered and published. Investigate missing terms, missing arm mappings and students flagged for manual review.

For final-class students:

- Use the graduation workflow only for students who satisfy the school's graduation rule or an explicit administrator decision.
- Graduated students leave the active student list and are shown in **Graduated students/archive**.
- Graduated students cannot sign in as active students or use normal public result checking.
- Administrators can inspect archived records and reports.
- If a graduation was a mistake, use the restore action and verify the student's class/status.

## 8. Fees, payments and receipts

### 8.1 Fee setup

1. In **Fees & payments > Fee types**, create Tuition, Uniform, Transport, Examination, Boarding or other fee types.
2. In **Assign fees to classes**, choose fee type, amount, academic session, term, classes and optionally one arm.
3. Review the resulting student assignments. Repeating the assignment skips students who already have the same fee for that session and term.
4. For an individual adjustment, use the student fee assignment workflow.

### 8.2 Gateway configuration

Payment checkout is intentionally unavailable until a gateway is configured. Set these only in the private cPanel `.env`:

```dotenv
PAYSTACK_SECRET_KEY=...
PAYSTACK_BASE_URL=https://api.paystack.co
FLUTTERWAVE_SECRET_KEY=...
FLUTTERWAVE_SECRET_HASH=...
FLUTTERWAVE_PUBLIC_KEY=...
FLUTTERWAVE_BASE_URL=https://api.flutterwave.com/v3
```

A payment return page is not proof of payment. The application verifies the transaction with the provider, validates amount/currency/reference, checks webhook signatures and makes settlement idempotent.

Configure provider webhooks:

- `https://YOUR-DOMAIN/api/webhooks/payments/paystack`
- `https://YOUR-DOMAIN/api/webhooks/payments/flutterwave`

Use provider test mode first with a disposable fee/student. Confirm the provider dashboard, the school payment event, the fee balance, receipt and notification before enabling live mode.

### 8.3 Payment notifications

Configure SMTP before enabling password reset or payment email notifications. Optional SMS uses Termii:

```dotenv
SMS_DRIVER=termii
TERMII_API_KEY=...
TERMII_SENDER_ID=YourSchool
TERMII_BASE_URL=https://api.ng.termii.com
TERMII_CHANNEL=generic
```

Set bursar/school notification addresses in the Portal settings. The scheduled queue worker sends queued email notifications every minute.

### 8.4 Payment history

Use **Payment history & debts** to filter by date/session/class/arm/student, view successful/pending/failed/refunded transactions, print reports, download PDF and download a spreadsheet. Reconcile verified payments with the provider dashboard and the school's bank statement.

## 9. Portal switches and security

In **Settings > Portal & website switches**, configure:

- Admission portal.
- Staff portal.
- Student portal.
- Parent portal.
- Results/check-result portal.
- PIN requirement for student login and result checking.
- Hold published results until fees are paid.
- Payment mode and minimum installment.
- Footer/admin visibility and notification settings where available.

Switches take effect across the chooser, home quick links, footer and login pages. Keep a portal off until its data and users are ready.

### Turnstile

Create a Cloudflare Turnstile widget for every production hostname, including the real domain and `www` if both are used. Set `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` in cPanel `.env`, then run `php artisan config:clear && php artisan optimize`. Keep `TURNSTILE_STRICT_CONTEXT=true` with real keys. Never put the secret key in GitHub, the ZIP, screenshots or a message.

### Security checklist

Use **Settings > Security check** and correct every warning:

- `APP_ENV=production` and `APP_DEBUG=false`.
- HTTPS and secure cookies.
- A unique strong `APP_KEY`.
- Working mail and payment settings if those functions are enabled.
- Turnstile enabled for public production login.
- Exactly the necessary number of Super Admin accounts.
- No demo accounts or temporary bootstrap credentials.
- Recent database and uploaded-file backups.

## 10. Scheduled tasks, queue and backups

Create this cPanel cron job every minute:

```text
/usr/local/bin/php /home/USERNAME/emmaculate/artisan schedule:run >> /dev/null 2>&1
```

The scheduler sends new-post/event notifications every five minutes and drains queued email jobs every minute. If the host does not support the PHP path above, use the absolute PHP binary shown by the host.

Back up both:

- MySQL database, including migrations, users, results, fees, payments and audit logs.
- `storage/app` uploaded pictures, documents and generated media.

Keep at least one backup off the server and test restoring a copy before results week.

## 11. Routine operating checklists

### Daily

- Review admissions and contact messages.
- Review payment events and failed/pending transactions.
- Review queued/failed jobs and mail delivery.
- Check backups and storage capacity during busy periods.

### Each term

- Create/activate the session and current term.
- Review classes, arms, subjects, teachers and assessment settings.
- Import or update students and run people synchronization.
- Assign fees and verify portal switches.
- Enter, review and publish results.
- Back up before promotion/graduation.

### Before results day

- Test a student login, parent login, teacher login and administrator login.
- Test one published report and one public result-check flow.
- Test PIN slips if PIN protection is enabled.
- Confirm domain HTTPS, Turnstile, mail and payment provider status.
- Use a cPanel plan with sufficient CPU, memory and entry processes; see `docs/TRAFFIC_PLAN_CPANEL.md`.

## 12. Troubleshooting

- **419 Page Expired:** refresh the login page and retry. The application now redirects stale browser sessions back to the correct form. Clear old site cookies if a browser keeps submitting an old form.
- **403 at `/admin`:** sign out of the portal account and use an account with an administrator role. A Parent/Teacher/Student account cannot access Filament.
- **No email:** check SMTP variables, DNS SPF/DKIM, the Laravel log and the queue cron job.
- **Payments unavailable:** verify that the gateway secret is present in the private `.env`, clear/rebuild config, and confirm the provider test/live mode.
- **Turnstile blocks every login:** check the widget hostname list, site/secret key pair and `TURNSTILE_STRICT_CONTEXT=true` for real keys.
- **CSV rows rejected:** download the template again and compare exact headers, class/arm names and date formats.
- **Pictures missing:** verify `storage/app` exists and the public media route can read the files; restore the uploaded-file backup if needed.
- **Slow or overloaded:** enable OPcache, use the scheduler rather than browser-triggered jobs, review database indexes/resource limits and follow `docs/TRAFFIC_PLAN_CPANEL.md`.

## 13. Important production rules

- Never use `migrate:fresh` on production.
- Never run seeders that replace or delete school data.
- Never put real passwords, payment keys, SMTP passwords or Turnstile secrets in the repository.
- Never share the Super Admin password.
- Never assume a payment return page means money was received.
- Always test changes on a copy or maintenance window before applying them to the live school site.

## 12. Results and people migration backups

Super Admins can use **Settings > Results & people backup**. Choose a term or all terms and download a Results ZIP. The archive contains a CSV matched by stable Student ID, subject code/name, academic session and term. It can be restored on a new domain or after accidental deletion; existing rows are updated and no rows are deleted by the importer.

Download a People ZIP for user login identifiers, roles, student/teacher/parent biodata and profile links. If a passphrase of at least 12 characters is supplied, the archive also contains a passphrase-encrypted credential file containing the existing Laravel password hashes. On the new domain, use the same passphrase during import; accounts and roles are recreated and users can sign in with their existing passwords. Plain-text passwords are never exported. Never send the passphrase by email or store it beside the ZIP.

When a term is marked current, the application automatically emails the previous term's Results ZIP and a People ZIP to the school email in **Website > School profile**. The automatic email deliberately excludes the password credential file; use the Super Admin download with a separate passphrase when migrating login access. This is the transition backup used when a completed/published term is replaced by a new current term. Ensure SMTP is configured and keep the attachments private.

These ZIP files contain confidential student results and personal data. Store them outside the public web root, restrict access to trusted administrators and test a restore on a non-production copy before using it for a live migration.
