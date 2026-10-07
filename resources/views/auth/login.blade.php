@php
    $studentOn = \App\Domain\Auth\Support\PortalAccess::studentEnabled();
    $parentOn = \App\Domain\Auth\Support\PortalAccess::parentEnabled();
    $checkerOn = \App\Domain\Auth\Support\PortalAccess::checkerEnabled();
@endphp
@extends('site.layout')
@section('title', 'Sign in | '.$settings->short_name)
@section('content')
<x-page-hero eyebrow="Secure school portal" title="Sign in" lead="Students sign in with their admission number and surname. Parents and staff sign in with their email address." :image="asset('storage/migrated-images/students-group.jpg')" :crumbs="[['Home', route('home')], ['Sign in', null]]" />
<section class="section">
    <div class="site-container" style="max-width:62rem">
        <div class="auth-grid">
            <div class="card card--pad auth-card">
                <h2>Students</h2>
                <p class="hint">Use your admission number and your surname in CAPITAL LETTERS. No email or password needed.</p>
                @unless($studentOn)<div class="alert alert--error" style="margin-bottom:1rem">The student portal is currently switched off by the school.</div>@endunless
                <form method="post" action="{{ route('student.login') }}" class="form-stack">
                    @csrf
                    <label class="field">Admission number<input name="admission_number" value="{{ old('admission_number') }}" required maxlength="60" autocomplete="off" autocapitalize="characters" class="upper-input" @if(! $studentOn) disabled @endif></label>
                    <label class="field">Surname (CAPITAL LETTERS)<input name="surname" required maxlength="80" autocomplete="off" autocapitalize="characters" class="upper-input" @if(! $studentOn) disabled @endif></label>
                    @error('admission_number')<p class="field__error" role="alert">{{ $message }}</p>@enderror
                    @error('surname')<p class="field__error" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="btn btn--maroon btn--block" @if(! $studentOn) disabled @endif>Sign in as student</button>
                </form>
            </div>
            <div class="card card--pad auth-card">
                <h2>Parents and staff</h2>
                <p class="hint">Sign in with the email address the school registered for you.</p>
                @unless($parentOn)<div class="alert alert--error" style="margin-bottom:1rem">The parent portal is currently switched off by the school. Staff can still sign in.</div>@endunless
                <form method="post" action="{{ route('login.submit') }}" class="form-stack">
                    @csrf
                    <label class="field">Email<input name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="username">@error('email')<span class="field__error" role="alert">{{ $message }}</span>@enderror</label>
                    <label class="field">Password<input name="password" type="password" required autocomplete="current-password">@error('password')<span class="field__error" role="alert">{{ $message }}</span>@enderror</label>
                    <label style="display:flex;gap:.5rem;align-items:center;font-size:.9rem"><input type="checkbox" name="remember" value="1" style="width:auto;margin:0"> Keep me signed in on this device</label>
                    <button type="submit" class="btn btn--navy btn--block">Sign in</button>
                </form>
                <p style="margin:1rem 0 0;font-size:.9rem"><a href="{{ route('password.request') }}" style="color:var(--maroon-700);font-weight:600;text-decoration:underline">Forgot your password?</a></p>
            </div>
        </div>
        @if($checkerOn)
            <div class="card card--pad" style="margin-top:1.2rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between">
                <div><strong>Just want to see a result?</strong><div class="muted" style="font-size:.92rem">You do not need to sign in. Use your admission number and surname.</div></div>
                <a class="btn btn--gold" href="{{ route('result-check') }}">Check result <x-site-icon name="arrow" /></a>
            </div>
        @endif
    </div>
</section>
@endsection
