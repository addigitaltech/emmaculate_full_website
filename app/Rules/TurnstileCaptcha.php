<?php

namespace App\Rules;

use App\Support\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Passes when the Cloudflare Turnstile check was completed for this form (or Turnstile is switched off). */
class TurnstileCaptcha implements ValidationRule
{
    public bool $implicit = true;

    /** @param string $action  the widget's data-action for the form being checked, e.g. "student_login" */
    public function __construct(private readonly string $action = 'login')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Turnstile::verify(is_string($value) ? $value : null, request()->ip(), $this->action, request()->getHost())) {
            $fail('Please complete the security check ("I am not a robot") and try again.');
        }
    }
}
