@if(\App\Support\Turnstile::enabled())
    <x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
        <div x-data x-init="window.onTurnstileDone = (token) => $wire.set('data.turnstile', token)" x-on:turnstile-reset.window="window.turnstile && window.turnstile.reset(); $wire.set('data.turnstile', null)">
            <div wire:ignore>
                <div class="cf-turnstile" data-sitekey="{{ \App\Support\Turnstile::siteKey() }}" data-callback="onTurnstileDone" data-theme="light"></div>
            </div>
        </div>
    </x-dynamic-component>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
