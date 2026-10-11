@extends('site.layout')
@section('title', 'Contact | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@php
    $lat = null;
    $lng = null;
    if (filled($settings->gps_location) && preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', (string) $settings->gps_location, $coords)) {
        $lat = (float) $coords[1];
        $lng = (float) $coords[2];
    }
@endphp
@section('content')
<x-page-hero eyebrow="Get in touch" title="Contact the School" lead="Send an enquiry and the school will respond using the contact details you provide." :image="$heroImage" :crumbs="[['Home', route('home')], ['Contact', null]]" />
<section class="section">
    <div class="site-container grid-2" style="align-items:start">
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.4rem">Visit or call us</h2><span class="rule" aria-hidden="true"></span>
            @if($settings->address)<div class="help-line"><x-site-icon name="pin" /><span>{{ $settings->address }}</span></div>@endif
            @if($settings->phone_primary)<div class="help-line"><x-site-icon name="phone" /><span><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_primary) }}">{{ $settings->phone_primary }}</a>@if($settings->phone_secondary)<br><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_secondary) }}">{{ $settings->phone_secondary }}</a>@endif</span></div>@endif
            @if($settings->email)<div class="help-line"><x-site-icon name="mail" /><a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></div>@endif
            @if($settings->office_hours)<div class="help-line"><x-site-icon name="clock" /><span>{{ $settings->office_hours }}</span></div>@endif
            @if(!$settings->address && !$settings->phone_primary && !$settings->email)<p class="muted">Contact details will be published here soon. Please use the form to reach the school.</p>@endif
        </div>
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.4rem">Send a message</h2><span class="rule" aria-hidden="true"></span>
            <form method="post" action="{{ route('contact.send') }}" class="form-grid form-grid--2">
                @csrf
                <label class="field">Your name<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">@error('name')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field">Email<input name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">@error('email')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field">Phone (optional)<input name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"></label>
                <label class="field">Subject (optional)<input name="subject" value="{{ old('subject', $prefillSubject) }}" maxlength="160"></label>
                <label class="field span-2">Message<textarea name="message" rows="6" required minlength="10" maxlength="5000">{{ old('message') }}</textarea>@error('message')<span class="field__error">{{ $message }}</span>@enderror</label>
                <div class="honeypot" aria-hidden="true"><label>Leave this field blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="span-2"><button type="submit" class="btn btn--maroon">Send message <x-site-icon name="send" /></button>
                <p class="muted" style="font-size:.8rem;margin-top:.8rem">Messages are stored securely for school follow-up. Do not include sensitive medical, financial or identity documents.</p></div>
            </form>
        </div>
    </div>
</section>
@if($lat !== null && $lng !== null)
<section class="section--tight">
    <div class="site-container">
        <div class="card" style="overflow:hidden">
            <div class="panel__head"><h2 class="h3">Find us on the map</h2>
                <span style="display:flex;gap:.6rem;flex-wrap:wrap">
                    <a class="btn btn--navy btn--sm" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $lat }},{{ $lng }}">Get directions</a>
                    <a class="btn btn--outline btn--sm" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&amp;query={{ $lat }},{{ $lng }}">Open in Google Maps</a>
                </span>
            </div>
            <iframe title="Map showing the school location" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                style="display:block;width:100%;height:380px;border:0"
                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lng - 0.006 }}%2C{{ $lat - 0.004 }}%2C{{ $lng + 0.006 }}%2C{{ $lat + 0.004 }}&amp;layer=mapnik&amp;marker={{ $lat }}%2C{{ $lng }}"></iframe>
        </div>
    </div>
</section>
@endif
@endsection
