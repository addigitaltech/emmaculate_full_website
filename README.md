# Emmaculate Academy — Unified School Platform

A single Laravel 12 application combining the inspected Emmaculate Academy public website, editable CMS, school administration, student/parent/teacher portals, academic results and fee-payment adapters. Public copy, visual identity and imported school photographs were sourced from the inspected website repository; result behavior was mapped from the inspected results portal. The original repositories remain unchanged.

## Requirements

- PHP 8.3+ with the Laravel-required extensions (`ctype`, `curl`, `dom`, `fileinfo`, `filter`, `mbstring`, `openssl`, `PDO`, `pdo_pgsql`, `tokenizer`, and `xml`).
- Composer 2, Node.js 20+ with npm, and PostgreSQL 14+.
- A production HTTPS hostname, a durable/shared filesystem or object store for uploaded media, and a configured mail provider for password-reset delivery.

## Install and run

```bash
cp .env.example .env
# Set APP_URL and DB_* in .env for your PostgreSQL database.
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan school:create-super-admin
php artisan serve --host=127.0.0.1 --port=8000
```

Open `/` for the public website, `/portal` for the CMS-managed portal launch cards, `/login` for the role-based school portal, and `/admin` for the authorized Filament administration panel. Portal cards set to **Live** link to their safe configured destination; **Coming Soon** cards are visible but non-clickable; **Hidden** cards are not shown publicly. Manage these under **Admin → Website → Portal links**. The bootstrap command prompts for one initial Super Admin name, email and hidden password. It refuses to create a second Super Admin. The project deliberately contains no default login, demo student accounts or real credentials. Configure mail before relying on password-reset emails.

The database seed creates the role/permission taxonomy, verified public school copy, supplied images and source-mapped grading defaults. It does not create students, teachers, parents, grades, fee obligations, a production contact channel or fictional news/events. Add real school records through the authorized administration panel after deployment.

## Tests and assets

```bash
composer validate --no-check-publish
php artisan test
npm run build
php artisan route:list --except-vendor
```

The test suite uses an isolated SQLite in-memory database and does not contact live payment services. Vite bundles Tailwind and the CSP-compatible Alpine runtime. Run `npm ci` before the first production asset build.

## Roles and data boundaries

`Super Admin`, `Website Admin`, `Content Editor`, `Results Admin`, `Finance Admin`, `Teacher`, `Student`, and `Parent` roles are seeded. The public application has no open registration. Administrators create accounts, assign one or more roles, link them to school profiles, and explicitly link each parent to their children. Students and parents see only published/linked records; teachers can enter only results for their assigned class/arm/subject; sensitive changes are audited. Result totals, grades, rankings and payment success are calculated or verified server-side. Result access is not payment-gated by default.

## Payment configuration and limits

Gateway credentials belong only in deployment environment variables; never put them in source, the database CMS, browser code, screenshots, or logs. Configure provider test credentials first, then configure the provider webhook URL as `https://your-domain.example/api/webhooks/payments/paystack` or `/api/webhooks/payments/flutterwave`, and test signature verification, amount/reference matching, idempotency and receipts before considering any live activation. `PAYSTACK_SECRET_KEY` is server-side. Flutterwave needs `FLUTTERWAVE_SECRET_KEY` and the provider-configured `FLUTTERWAVE_SECRET_HASH` for checkout/webhook support. Provider enablement is a separate school-admin setting and is off by default. No merchant credentials or entitlement were supplied, so no real transaction was initiated or tested.

The payment return URL is not proof of payment. The service confirms amount, currency and reference through the provider API before settling a fee and issuing a receipt. Paystack refund requests remain pending until a signed refund lifecycle event confirms processing; only the documented `refund.processed` state marks a payment reversed. Flutterwave's API response may represent an initiated refund still awaiting disbursement, and its refund webhooks are not enabled by default. Until the school obtains that webhook entitlement or an operator reconciles the provider dashboard, the application records the request as accepted and does not claim the customer's funds were returned. Ambiguous refund requests are not automatically retried. The inspected Moniepoint documentation describes POS capabilities, not a confirmed hosted online checkout; Moniepoint is therefore unavailable for online checkout.

## Production operations

Before publishing: set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, use a strong unique `APP_KEY`, force HTTPS and secure session cookies at the trusted reverse proxy, restrict allowed origins to the application's own host, and use database credentials limited to this application. Never use SQLite-in-memory for production. Set durable object storage for media (or a persistent shared `public` disk), restrict and back up database and media separately, and test restoration. Configure an actual mail transport, monitor Laravel/provider logs without logging secrets or raw payment payloads, and retain HTTPS availability for provider webhooks. Use the deployment's secret manager for payment secrets and rotate them through the provider's approved process.

The ZIP excludes `.env`, credentials, `vendor/`, `node_modules/`, build caches and temporary files. Read [the source feature/architecture map](docs/architecture/source-feature-map.md) and [verified payment-provider notes](docs/architecture/payment-provider-notes.md) before adapting unconfirmed school facts or enabling integrations.
