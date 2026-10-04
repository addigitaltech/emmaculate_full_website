@extends('site.layout')
@section('title', 'Contact | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
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
@endsection
