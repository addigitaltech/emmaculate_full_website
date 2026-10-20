<?php

namespace App\Rules;

use App\Support\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Passes when the Cloudflare Turnstile check was completed (or Turnstile is switched off). */
class TurnstileCaptcha implements ValidationRule
{
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Turnstile::verify(is_string($value) ? $value : null, request()->ip())) {
            $fail('Please complete the security check ("I am not a robot") and try again.');
        }
    }
}
