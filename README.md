# School Website and Results Portal

One Laravel 12 application for a school: public website (editable from the admin area), admission applications, student, parent and teacher portals, results with printable A4 report cards, student PIN checking, fee payments, and an archive of graduated students. Every school-specific detail (name, logo, contacts, Student ID prefix, grading, portals) is edited in the admin area, so the same code can run for any school.

## Where can it run?

| Platform | Works | Notes |
|---|---|---|
| **cPanel / shared hosting** | Yes (the production route) | PHP 8.3, MySQL/MariaDB. Use the ready-built release ZIP. Guide: [docs/CPANEL_DEPLOYMENT.md](docs/CPANEL_DEPLOYMENT.md) |
| **XAMPP, WAMP, MAMP, Laragon (own computer)** | Yes | Needs **PHP 8.3+** (older XAMPP builds with PHP 8.2 will not work). Guide: [docs/INSTALL_XAMPP.md](docs/INSTALL_XAMPP.md) |
| **Render, Railway or any Docker host** | Yes | `Dockerfile` included (PHP 8.3 + Apache, PostgreSQL or MySQL). |
| **VPS (Ubuntu + Nginx/Apache)** | Yes | Standard Laravel deployment; point the web root at `public/`. |
| Windows / macOS / Linux | Yes | Any system that runs PHP 8.3, Composer and Node. |

Databases: **MySQL 5.7+ / MariaDB 10.3+**, **PostgreSQL 14+** and **SQLite** (tests only). The test suite runs on all three in GitHub Actions on every push.

## Requirements
- PHP **8.3+** with: `ctype, curl, dom, fileinfo, filter, gd, intl, mbstring, openssl, pdo_mysql (or pdo_pgsql), tokenizer, xml, zip, bcmath, exif`
- Composer 2 and Node.js 20+ (only to *build*; on cPanel you upload a ready-built ZIP instead)
- HTTPS in production, a working mail service (for "Forgot password"), and durable file storage for uploads (cPanel disks are durable; Render's free plan is not)

## Quick start (any computer)
```bash
cp .env.example .env          # then set APP_URL and the DB_* lines (see the platform guides)
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci && npm run build
php artisan school:create-super-admin
php artisan serve             # http://127.0.0.1:8000 (website), /admin (admin area)
```
Step-by-step for XAMPP: [docs/INSTALL_XAMPP.md](docs/INSTALL_XAMPP.md). For cPanel: [docs/CPANEL_DEPLOYMENT.md](docs/CPANEL_DEPLOYMENT.md) (you can build the upload ZIP from GitHub: **Actions > Build cPanel release > Run workflow**).

## Main features
- **Student ID** made automatically (e.g. `EAC/2627/0001`: school prefix / short session / running number). The prefix is set in School settings.
- **Results**: enter by student or subject, class teacher remarks and ratings, publish per class, positions and cumulative results, A4 portrait report cards (print or PDF).
- **Checking results**: students/parents sign in with Student ID, surname and an optional PIN; the public Check Result page lets people choose the session and term. PIN use is a switch in Portal settings.
- **PIN slips** printed in bulk for a class or the whole school.
- **Promotion and graduation**: move classes up each session; the final class (e.g. SS3) graduates into an archive that only administrators can open.
- **Fees and payments**: Paystack and Flutterwave, verified with the provider before anything is marked paid; receipts; optional "hold results until fees are paid".
- **Security**: Cloudflare Turnstile on every sign-in (optional, free), strict security headers, per-student lockouts, strong passwords, activity log. See [docs/SECURITY.md](docs/SECURITY.md).
- **Simple admin menu** with "Start here" buttons. See [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md).

## Roles
`Super Admin`, `Website Admin`, `Content Editor`, `Results Admin`, `Finance Admin`, `Teacher`, `Student` and `Parent`. Permissions are enforced on the server, not only hidden in the menu. A teacher can enter scores only for their own class and subject; behaviour ratings and the class teacher's remark belong to the arm's class teacher.

## Tests and assets
```bash
php artisan test            # runs on an in-memory SQLite database
npm run build
```
GitHub Actions (`.github/workflows/tests.yml`) runs the suite on SQLite, MySQL and PostgreSQL for every push.

## Payments
Keep gateway keys only in the deployment environment (`.env` or the host's variables), never in the database, source or screenshots. A payment return page is not proof of payment: the server confirms amount, currency and reference with the provider, and webhooks are signature-checked and idempotent. Set the webhook URLs in each provider dashboard to `https://YOUR-DOMAIN/api/webhooks/payments/paystack` and `.../flutterwave`.

## Going live checklist
`APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL` (https), strong unique `APP_KEY`, mail configured, Turnstile keys set, demo content removed, backups scheduled, Cloudflare in front. Details: [docs/PERFORMANCE.md](docs/PERFORMANCE.md) and [docs/SECURITY.md](docs/SECURITY.md).

## More documents
`docs/ADMIN_GUIDE.md` (where to change things), `docs/EMAIL_SETUP.md`, `docs/TRAFFIC_PLAN_CPANEL.md`, `docs/CBT_INTEGRATION_PLAN.md` (connecting a CBT/exam website), `docs/CHANGES_UPDATE_*.md` (what each update changed).
