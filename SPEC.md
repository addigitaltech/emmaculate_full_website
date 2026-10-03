# Specification: Emmaculate Full Website

## Objective
Build one fresh Laravel application for Emmaculate Academy that combines the inspected premium public website, a database-managed CMS, school/results administration, role-specific portals, media, payments and audit/security features. Preserve the website's established design and factual school content; preserve the results portal's useful behavior while replacing unsafe patterns with explicit Laravel authorization and server-side calculations. Deliver the working source project as `emmaculate-full-website.zip`; no GitHub repository creation or publication is required for this delivery.

## Actors and outcomes
- Public visitors can browse school pages, leadership, academics, admissions, published news/events, gallery, FAQs, announcements and contact information.
- Website/content admins can manage branding, content, navigation, footer, SEO and media without changing source code.
- Results admins can manage people, academic structure, assessments, scores, grade ranges, publication and report cards.
- Teachers can work only within assigned classes/subjects/students.
- Students can view only their own published results and permitted academic records.
- Parents can view only children explicitly linked to their account, along with permitted payments.
- Finance admins can manage fees, gateway configuration, transaction verification, receipts and supported refunds.
- Super admins can administer roles/permissions and system configuration.

## Tech stack and decisions
- PHP 8.3+ and Laravel 12, selected for a supported PHP/Laravel baseline.
- PostgreSQL as the default database because both inspected source applications use PostgreSQL; deployments may set their own PDO-compatible database via environment configuration.
- Server-rendered Blade public pages for SEO and direct content delivery; Vite, Tailwind CSS and Alpine.js for responsive interactions; separate public, CMS and portal layouts.
- Laravel cookie/session authentication with hashed passwords, throttling, CSRF, explicit role/permission checks and resource policies. Use Sanctum for any authenticated JSON API; provider webhooks use dedicated public routes with provider-specific verification and idempotency.
- Domain-oriented application code under `app/Domain/{IdentityAccess,Website,Media,SchoolManagement,Results,Payments,Audit,Notifications,IntegrationApi}`; standard Laravel HTTP adapters remain thin.
- Laravel HTTP client for Paystack and Flutterwave adapters. Moniepoint uses the same gateway contract, but is not represented as an online checkout until the exact merchant product is verified; when access/product details are unavailable, expose configuration status and document the limitation rather than faking behavior.

## Commands
```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* and mail/storage settings in .env, then:
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan test
composer validate --no-check-publish
php artisan serve --host=127.0.0.1 --port=8000
```

## Project structure
```text
app/Domain/IdentityAccess/  Users, roles, permissions, sessions and policies
app/Domain/Website/         Public pages, CMS actions, SEO and school settings
app/Domain/Media/           Upload validation, media records and storage boundary
app/Domain/SchoolManagement/People, household links, classes, arms, subjects, sessions
app/Domain/Results/         Assessment, score, grading, ranking, publication and reports
app/Domain/Payments/        Fees, gateway contract/drivers, transactions and receipts
app/Domain/Audit/           Append-only sensitive activity records
app/Domain/Notifications/   Contact/admission/payment/result notices
app/Domain/IntegrationApi/  Versioned APIs and provider webhook adapters
app/Http/                   Thin controllers, middleware, requests and API resources
routes/                     Public, admin, portal, API and webhook route boundaries
resources/views/            Public site, CMS, portals, receipts and report cards
resources/css, resources/js Vite/Tailwind and small interactive behaviors
 database/migrations         Relational schema with constraints and indexes
 database/seeders            Confirmed school copy/media and safe initial roles
 tests/Unit, tests/Feature   Domain and HTTP authorization/security tests
 docs/                       Specification, migration map, plan and operator guidance
```

## Code style
Use strict request validation, explicit policies, small domain services, framework query binding, and transactions around score/payment state changes. Example:

```php
final class PublishResult
{
    public function handle(Result $result, User $actor): Result
    {
        Gate::forUser($actor)->authorize('publish', $result);

        return DB::transaction(function () use ($result, $actor): Result {
            $result->recalculateFromPersistedScores();
            $result->publish();
            AuditEntry::record($actor, 'result.published', $result);

            return $result->refresh();
        });
    }
}
```

Never accept client-supplied totals, grades, ranks or payment-success flags as authoritative. CMS rich content uses a safe block schema; navigation accepts internal routes or validated HTTPS URLs only. Never log secrets, tokens, passwords or card security data.

## Testing strategy
- Pest/PHPUnit feature tests for authentication, role/permission boundaries, ownership/IDOR protection, CMS validation, upload constraints and public-only published content.
- Unit tests for result formulas, grade-band coverage, shared ranking ties, third-term cumulative rules, gateway signatures and idempotent state transitions.
- Laravel HTTP fakes for provider API behavior; tests do not require real credentials or claim live payment capability.
- `php artisan test`, `composer validate`, and `npm run build` as available. Zip verification confirms the archive includes source, lockfiles, `.env.example`, docs, migrations, tests and imported media, but excludes `.env`, credentials, `vendor/`, `node_modules/` and caches.

## Boundaries
- Always: keep both supplied repositories read-only; use only verified source facts; preserve confirmed content/design; enforce authorization on the server; use migrations/constraints; keep secrets out of source, browser code and logs; document unavailable external credentials/product access.
- Ask first: destructive data/schema changes, connecting to a live school database, selecting an unverified Moniepoint product, or activating real gateway credentials/payments.
- Never: copy the old generic `/api/{table}` access pattern; trust browser result/payment state; store PAN/CVV; allow arbitrary server code/CSS/JS in CMS content; fabricate missing school facts, credentials, live records or provider behavior.

## Success criteria
1. The ZIP contains one installable Laravel project with migrations, seeders, documentation, assets and automated tests; no secrets or source-repository edits are included.
2. Public routes retain the source website's visual identity and route/content coverage while pulling editable content/settings/media from persistent records.
3. CMS provides authorized editing workflows for school settings/branding, homepage, pages/history/identity content, academics/curriculum, admissions, leadership, news, events, gallery, FAQs, navigation/footer, announcements, SEO and media.
4. Admin, teacher, student and parent workflows use a unified Laravel session identity and server-side authorization; parent-child links are many-to-many; students/parents/teachers cannot escape their record scope.
5. Result computation, grade coverage, ranking/ties, publication and print/report-card behavior are calculated or verified server-side and preserve the mapped source rules.
6. Fee assignments are flexible; Paystack and Flutterwave use modular server-side initiate/verify/webhook paths with signature validation and idempotency; Moniepoint is represented honestly according to verified merchant API support; verified payments produce records/receipts, and results are not payment-blocked by default.
7. Security, audit, upload handling, SEO, documentation and archive checks are implemented and reported with evidence; missing production data, credentials or merchant entitlements are documented as limitations.

## Open questions handled without fabricated defaults
- No live database dump, student list, payment credential or production mail credential is present in the inspected repositories; seed only confirmed public facts and assets.
- The exact Moniepoint merchant/API product for online school fee checkout must be confirmed by the owner; the inspected official API source is POS-oriented.
- Production domain, contact/office/social details and any missing curriculum/biographies remain unset or explicitly awaiting school confirmation.
