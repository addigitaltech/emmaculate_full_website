<?php

namespace App\Filament\Pages\Auth;

use App\Rules\TurnstileCaptcha;
use App\Support\Turnstile;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\ViewField;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

/** The admin sign-in page with the Cloudflare Turnstile check added (only when Turnstile is switched on). */
class Login extends BaseLogin
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                        $this->getTurnstileFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getTurnstileFormComponent(): Component
    {
        return ViewField::make('turnstile')
            ->view('filament.forms.turnstile')
            ->hiddenLabel()
            ->visible(fn (): bool => Turnstile::enabled())
            ->rules(Turnstile::enabled() ? ['required', new TurnstileCaptcha()] : [])
            ->validationMessages(['required' => 'Please complete the security check ("I am not a robot") and try again.']);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $exception) {
            // A Turnstile token works once; show a fresh check after any failed attempt.
            $this->dispatch('turnstile-reset');

            throw $exception;
        }
    }
}
