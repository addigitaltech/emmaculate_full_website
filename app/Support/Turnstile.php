<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile, a free, privacy-friendly "I am not a robot" check used on every sign-in form.
 * It is switched on by putting TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY in the .env file (see docs/SECURITY.md);
 * without both keys it is off, so local installs (XAMPP, tests) work without any setup.
 */
final class Turnstile
{
    public const FIELD = 'cf-turnstile-response';

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    /** True when the visitor's token is confirmed by Cloudflare (always true while Turnstile is switched off). */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        if (! self::enabled()) {
            return true;
        }
        if (! filled($token) || strlen((string) $token) > 2048) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (\Throwable) {
            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
