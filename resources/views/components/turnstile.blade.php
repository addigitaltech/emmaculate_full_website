@props(['action' => 'login'])
@if(\App\Support\Turnstile::enabled())
    <div class="cf-turnstile" data-sitekey="{{ \App\Support\Turnstile::siteKey() }}" data-action="{{ $action }}" data-theme="light" style="min-height:65px"></div>
    @error(\App\Support\Turnstile::FIELD)<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
