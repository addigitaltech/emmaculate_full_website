<?php

namespace App\Filament\Pages;

use App\Domain\Identity\DemoAccounts;
use App\Models\User;
use Filament\Pages\Page;

/** Super Admin: a plain-language health check of the website's security settings, with what to do about each problem. */
class SecurityCheck extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Security check';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Security check';
    protected static ?string $slug = 'security-check';
    protected static string $view = 'filament.pages.security-check';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('Super Admin');
    }

    /** @return array<int, array{label: string, ok: bool, fix: string}> */
    public function checks(): array
    {
        $production = app()->environment('production');
        $mailer = (string) config('mail.default');
        $url = (string) config('app.url');
        $demo = class_exists(DemoAccounts::class) && DemoAccounts::exists();

        return [
            ['label' => 'Debug mode is off', 'ok' => ! config('app.debug'), 'fix' => 'Set APP_DEBUG=false in the .env file (or Render environment). Debug mode shows secrets in error pages.'],
            ['label' => 'Running as "production"', 'ok' => $production, 'fix' => 'Set APP_ENV=production on the live website.'],
            ['label' => 'The website address uses https', 'ok' => str_starts_with($url, 'https://') || ! $production, 'fix' => 'Set APP_URL to your https:// address and install the free SSL certificate in cPanel (or use Cloudflare).'],
            ['label' => 'This page was opened over a secure connection', 'ok' => request()->isSecure() || ! $production, 'fix' => 'Use the https:// address. Never sign in over plain http on the internet.'],
            ['label' => 'Login cookies are marked secure', 'ok' => (bool) config('session.secure') || ! $production, 'fix' => 'Set SESSION_SECURE_COOKIE=true in the .env file.'],
            ['label' => 'Sign-in "I am not a robot" check (Cloudflare Turnstile) is on', 'ok' => \App\Support\Turnstile::enabled(), 'fix' => 'Add TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY (docs/SECURITY.md).'],
            ['label' => 'Email can be sent (needed for password resets and payment receipts)', 'ok' => ! in_array($mailer, ['log', 'array'], true), 'fix' => 'Set the MAIL_* lines (docs/EMAIL_SETUP.md).'],
            ['label' => 'Payment keys are set in the host settings', 'ok' => filled(config('services.paystack.secret_key')) || filled(config('services.flutterwave.secret_key')), 'fix' => 'Add the Paystack or Flutterwave keys in .env (never in the database or code).'],
            ['label' => 'No demo logins are left on the website', 'ok' => ! $demo, 'fix' => 'Settings > Demo logins > Remove demo logins.'],
            ['label' => 'Few Super Admin logins (2 or fewer)', 'ok' => User::role('Super Admin')->count() <= 2, 'fix' => 'Give most people a smaller role (Students & staff > Users & logins).'],
            ['label' => 'Application key is set', 'ok' => filled(config('app.key')), 'fix' => 'Run php artisan key:generate (or set APP_KEY).'],
            ['label' => 'PHP version is 8.3 or newer', 'ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'fix' => 'Choose PHP 8.3+ in your hosting control panel.'],
            ['label' => 'PHP does not print errors to visitors', 'ok' => ! filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN) || ! $production, 'fix' => 'Set display_errors=Off in php.ini (the Docker image already does).'],
        ];
    }
}
