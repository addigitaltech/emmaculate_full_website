# Production cPanel deployment

This is the production deployment procedure for a school-owned cPanel server. The release ZIP contains the Laravel application, Composer production dependencies and built CSS/JavaScript. It does not contain `.env`, real credentials, Git metadata, Node modules or test files.

For day-to-day operation after deployment, read [`SUPER_ADMIN_SETUP_GUIDE.md`](SUPER_ADMIN_SETUP_GUIDE.md). For the student upload file, read [`CSV_STUDENT_IMPORT_FORMAT.md`](CSV_STUDENT_IMPORT_FORMAT.md).

## 1. Confirm the hosting plan

Before purchasing or deploying, confirm that the host provides:

- PHP 8.3 or newer with `bcmath`, `ctype`, `curl`, `dom`, `exif`, `fileinfo`, `filter`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml` and `zip`.
- MySQL 5.7+/8 or MariaDB 10.3+ with `utf8mb4`.
- HTTPS/AutoSSL and a real domain.
- A document-root setting so the domain points to the application's `public` directory.
- At least 1–2 GB RAM and enough entry processes for the school's expected traffic.
- cPanel Terminal/SSH or a way to run a one-time PHP command.
- Cron jobs that can run every minute.
- A durable home directory and a backup facility for the database and uploaded files.

LiteSpeed with PHP OPcache is preferred. Do not point the public domain at the Laravel project root; point it at `public`.

## 2. Obtain the release ZIP

### Option A: build locally

On a computer with PHP 8.3+, Composer 2, Node.js 20+ and `zip`:

```bash
bash deploy/build-cpanel-release.sh
```

The script runs a production Composer install, builds Vite assets, checks the release contents and creates a file named like `emmaculate-cpanel-release-20261103-1200.zip`.

### Option B: GitHub Actions

Run the repository workflow named **Build cPanel release**, then download its `cpanel-release` artifact. Inspect the artifact contents before uploading it. Do not upload a ZIP that contains `.env`, `.git`, internal development files, `tests`, `node_modules` or real secrets.

## 3. Create the cPanel database

In **cPanel > MySQL Databases**:

1. Create a database.
2. Create a database user with a long unique password.
3. Add the user to the database with **ALL PRIVILEGES**.
4. Record the full cPanel-prefixed names, for example `cpaneluser_school` and `cpaneluser_schooluser`.
5. Do not use the cPanel account password as the database password.

## 4. Upload the application

1. Upload the release ZIP to `/home/USERNAME/`, not directly into `public_html`.
2. Extract it into `/home/USERNAME/emmaculate`.
3. Confirm that `/home/USERNAME/emmaculate/artisan` exists and that `/home/USERNAME/emmaculate/public/index.php` exists.
4. Keep `storage/app` and the `.env` file outside the public document root.
5. Set the domain's document root to:

```text
/home/USERNAME/emmaculate/public
```

If the host cannot change the document root, use a `public_html` workaround only with the host's approval: copy the contents of `public/` into `public_html/` and change both paths in `public_html/index.php` to the absolute `/home/USERNAME/emmaculate/vendor/autoload.php` and `/home/USERNAME/emmaculate/bootstrap/app.php` paths. Never copy the entire application into a public directory.

## 5. Create the production `.env`

From the application directory:

```bash
cd /home/USERNAME/emmaculate
cp .env.cpanel.example .env
```

Edit `.env` and set at minimum:

```dotenv
APP_NAME="Your School Name"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://www.yourschooldomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_school
DB_USERNAME=cpaneluser_schooluser
DB_PASSWORD=the-long-database-password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Use a real SMTP provider before enabling password resets or payment notifications:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.example
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=null
MAIL_FROM_ADDRESS=noreply@yourschooldomain.com
MAIL_FROM_NAME="Your School Name"
```

For the first bootstrap only, set these temporary values:

```dotenv
SCHOOL_ADMIN_NAME="First Administrator"
SCHOOL_ADMIN_EMAIL=admin@yourschooldomain.com
SCHOOL_ADMIN_PASSWORD="A unique 12+ character password with upper/lower case, number and symbol"
```

Do not commit or send the completed `.env` file. After the first setup, remove all three `SCHOOL_ADMIN_*` lines.

## 6. First-time commands

Run the following from cPanel Terminal/SSH. Use the host's PHP 8.3 binary if `php` points to an older version.

```bash
cd /home/USERNAME/emmaculate
php -v
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force
php artisan migrate --force
php artisan school:init
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

`school:init` is safe to run on a new installation and on later deploys. It seeds missing baseline data, creates the first Super Admin only when no Super Admin exists and synchronizes identity records. It does not replace the production database.

If `storage:link` is blocked, create the link in File Manager if supported:

```text
public/storage -> ../storage/app/public
```

Then visit `/admin`, sign in, confirm the dashboard loads and remove the temporary `SCHOOL_ADMIN_*` values:

```bash
php artisan config:clear
php artisan optimize
```

## 7. No Terminal available

Use a one-time cPanel cron job scheduled a few minutes in the future. Put the real values in `.env` first, then run:

```text
/usr/local/bin/php /home/USERNAME/emmaculate/artisan migrate --force && /usr/local/bin/php /home/USERNAME/emmaculate/artisan school:init && /usr/local/bin/php /home/USERNAME/emmaculate/artisan storage:link && /usr/local/bin/php /home/USERNAME/emmaculate/artisan optimize
```

Delete the one-time cron job after it succeeds. Never put the database password or Super Admin password in a cron command or a URL.

## 8. Required cron job

Create this recurring cPanel cron job to run every minute:

```text
/usr/local/bin/php /home/USERNAME/emmaculate/artisan schedule:run >> /dev/null 2>&1
```

The scheduler:

- Sends newly published news/event notifications every five minutes.
- Checks daily at 02:00 for a current or upcoming term within seven days and emails the previous term's Results ZIP plus the non-credential People ZIP to the school email. Each term is sent once.
- Drains queued email jobs every minute using `queue:work --stop-when-empty`.
- Prevents overlapping scheduled work.

If the provider uses a different PHP path, use the absolute PHP 8.3 path shown in cPanel's MultiPHP/Terminal documentation.

## 9. Payment gateway setup

Payment configuration is deliberately empty in the release. The application cannot verify live payments until private provider credentials are added to `.env`.

### Paystack

```dotenv
PAYSTACK_SECRET_KEY=your-private-secret
PAYSTACK_BASE_URL=https://api.paystack.co
```

In the Paystack merchant dashboard, open the webhook settings and register this exact **HTTPS POST** endpoint:

```text
https://YOUR-DOMAIN/api/webhooks/payments/paystack
```

Paystack signs the raw request body in the `x-paystack-signature` header. Laravel validates that header with HMAC-SHA512 using `PAYSTACK_SECRET_KEY`; do not modify, re-encode or proxy the JSON body before it reaches Laravel. The application processes `charge.success` only after it asks Paystack to verify the transaction and confirms the reference, amount, currency and successful status. Paystack refund events are also handled for refund status updates.

### Flutterwave

```dotenv
FLUTTERWAVE_SECRET_KEY=your-secret-key
FLUTTERWAVE_SECRET_HASH=your-webhook-secret-hash
FLUTTERWAVE_PUBLIC_KEY=your-public-key
FLUTTERWAVE_BASE_URL=https://api.flutterwave.com/v3
```

In the Flutterwave merchant dashboard, open the webhook settings and register this exact **HTTPS POST** endpoint:

```text
https://YOUR-DOMAIN/api/webhooks/payments/flutterwave
```

Set the same Flutterwave webhook secret/hash value in the provider dashboard and in `FLUTTERWAVE_SECRET_HASH`. Laravel validates the provider's `verif-hash` header before parsing the body. The application processes a `charge.completed` event only when its data status is `successful`, then verifies the provider transaction ID with Flutterwave and confirms the `tx_ref`, amount and currency.

### Webhook rules that apply to both providers

- Use the final HTTPS domain, including the correct `www` or non-`www` form. Do not register a temporary cPanel hostname, `http://` URL or local URL.
- The endpoint is a public POST endpoint; it does not require a user login or CSRF token. Do not add Basic Auth, a login redirect or a maintenance-page rule in front of it.
- The application applies a rate limit of 120 webhook requests per minute per source IP. Do not put a cache or HTML redirect in front of the endpoint.
- A valid but unmatched event is acknowledged and recorded as `unmatched`; confirm that the provider reference/transaction ID and local transaction reference belong to the same payment.
- A duplicate event is acknowledged without a second settlement, receipt or provider verification. Events are tracked in the `payment_events` table by a deterministic provider event ID.
- Invalid signatures return HTTP 401. Invalid JSON returns HTTP 400. Temporary processing failures return HTTP 500 so the provider can retry.
- A provider return/redirect page is not proof of payment. The server-side verification and webhook processing are the source of truth.

After editing the production `.env`, reload Laravel's configuration before testing:

```bash
cd /home/USERNAME/emmaculate
php artisan optimize:clear
php artisan config:cache
```

Do not test by pasting secret keys into a URL, browser console, GitHub issue or support message. Keep the provider secret keys and Flutterwave webhook hash only in the server environment.

### Test-mode verification checklist

Test in provider test mode first:

1. Configure one gateway's test credentials and its test webhook endpoint value, using the same public HTTPS domain.
2. Create one disposable student, fee type and fee assignment.
3. Complete one test checkout from the parent portal.
4. Confirm that the provider reports the transaction as successful.
5. Confirm that the local payment changes to successful, the fee balance changes, a receipt is created and the payment event is marked processed.
6. Deliver or replay the same provider webhook once more and confirm that no second receipt or fee settlement is created.
7. Check the application log and provider delivery log for any signature, URL, response-code or timeout errors.

Only then switch the provider and keys to live mode. Never mark a payment manually just because a return page was displayed.

The application verifies the provider response, amount, currency and reference, checks webhook signatures and prevents duplicate settlement. The automated regression coverage is in `tests/Feature/PaymentWebhookTest.php` and covers valid signatures, invalid signatures, successful settlement and duplicate delivery for both providers.

## 10. Mail, Turnstile and SMS

### Cloudflare Turnstile

Create a Turnstile widget whose allowed domains include the real domain and `www` if both are used:

```dotenv
TURNSTILE_SITE_KEY=public-site-key
TURNSTILE_SECRET_KEY=private-secret-key
TURNSTILE_HOSTNAMES=www.yourschooldomain.com,yourschooldomain.com
TURNSTILE_STRICT_CONTEXT=true
```

After changing `.env`:

```bash
php artisan config:clear
php artisan optimize
```

### Optional Termii SMS

```dotenv
SMS_DRIVER=termii
TERMII_API_KEY=private-termii-key
TERMII_SENDER_ID=YourSchool
TERMII_BASE_URL=https://api.ng.termii.com
TERMII_CHANNEL=generic
```

Keep all private keys only in cPanel's `.env` or a private environment-variable manager.

## 11. Post-install validation

Perform these checks before announcing the site:

1. Open the homepage, contact page, admission page and public result checker.
2. Open `/admin`, sign in, sign out and sign in again.
3. Verify the Super Admin dashboard and security check.
4. Create a test teacher, parent and student account, then test `/staff/login`, `/parent/login` and `/student/login`.
5. Import two rows using `docs/student-import-example.csv` on a test/copy database or with real school rows after backup.
6. Confirm the generated Student IDs and class/arm links.
7. Create one academic session/term, class, arm, subject and teacher assignment.
8. Enter and publish one test result, then open its report/PDF.
9. Create one fee assignment. If payments are enabled, run one provider test transaction and verify the webhook/receipt.
10. Submit a password reset request and confirm the email arrives.
11. Check that uploaded media cannot execute as scripts and that the document root does not expose `.env`, `vendor` or storage internals.
12. Review the error log and queue/cron output.

## 12. Updating the live installation

Before updating:

1. Back up the database and `/home/USERNAME/emmaculate/storage/app`.
2. Put the site in maintenance mode only if the migration requires it.
3. Upload the new release beside the old release or into a temporary directory.
4. Keep the existing `.env` and `storage/app`.
5. Point the document root to the new `public` directory after checking the files.
6. Run:

```bash
cd /home/USERNAME/emmaculate
php artisan migrate --force
php artisan school:init
php artisan optimize:clear
php artisan optimize
```

7. Open the homepage, `/admin`, one portal and one report before closing maintenance mode.

Never run `migrate:fresh`, `db:wipe`, demo seeders or destructive database commands on the live school database.

## 13. Backup and rollback

Back up both the MySQL database and `storage/app`. A source-code ZIP alone is not a school backup because it does not contain uploaded pictures, student photos, generated media, users, fees or results.

Keep at least one off-server copy and periodically test restoration on a separate database. For rollback, restore the prior code release and database backup together if a migration changed the schema; do not restore only old code against a newer schema without checking compatibility.

## 14. What the release contains and does not contain

Included:

- Laravel application source.
- Production Composer dependencies.
- Built Vite CSS/JavaScript and public assets.
- Database migrations and seeders.
- Public documentation and setup guides.
- Student CSV format and example.

Excluded:

- `.env` and all real credentials.
- Git history and GitHub workflow files.
- `node_modules` and development dependencies.
- Tests and PHPUnit cache.
- Docker-only files.
- Internal development-only files.
