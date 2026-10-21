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

    /**
     * True when the visitor's token is confirmed by Cloudflare (always true while Turnstile is switched off).
     * Besides "success", the answer must carry the action of the form being submitted and the hostname this site is
     * served on, so a token earned on another form or another website cannot be reused here.
     */
    public static function verify(?string $token, ?string $ip = null, ?string $expectedAction = null, ?string $requestHost = null): bool
    {
        if (! self::enabled()) {
            return true;
        }
        if (! filled($token) || strlen((string) $token) > 2048) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            return false;
        }
        if (! filter_var(config('services.turnstile.strict_context', true), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if ($expectedAction !== null && $response->json('action') !== $expectedAction) {
            return false;
        }

        $allowed = collect(explode(',', (string) config('services.turnstile.hostnames')))
            ->map(fn ($host) => strtolower(trim($host)))->filter()->values();
        if ($allowed->isEmpty() && filled($requestHost)) {
            $allowed = collect([strtolower((string) $requestHost)]);
        }

        return $allowed->contains(strtolower((string) $response->json('hostname')));
    }
}
