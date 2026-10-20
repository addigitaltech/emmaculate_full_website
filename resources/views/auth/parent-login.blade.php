@extends('site.layout')
@section('title', 'Parent Portal | '.$settings->short_name)
@section('content')
<x-page-hero eyebrow="Parent Portal" title="Parent sign-in" lead="Sign in with the email address you gave the school to see your children's results and fees." :image="asset('storage/migrated-images/textbooks.jpg')" :crumbs="[['Home', route('home')], ['Parent Portal', null]]" />
<section class="section">
    <div class="site-container" style="max-width:34rem">
        <div class="card card--pad auth-card">
            <form method="post" action="{{ route('login.parent.submit') }}" class="form-stack">
                @csrf
                <label class="field">Email<input name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="username">@error('email')<span class="field__error" role="alert">{{ $message }}</span>@enderror</label>
                <label class="field">Password<input name="password" type="password" required autocomplete="current-password">@error('password')<span class="field__error" role="alert">{{ $message }}</span>@enderror</label>
                <label style="display:flex;gap:.5rem;align-items:center;font-size:.9rem"><input type="checkbox" name="remember" value="1" style="width:auto;margin:0"> Keep me signed in on this device</label>
                <x-turnstile />
                <button type="submit" class="btn btn--navy btn--block">Sign in</button>
            </form>
            <p style="margin:1rem 0 0;font-size:.9rem"><a href="{{ route('password.request') }}" style="color:var(--maroon-700);font-weight:600;text-decoration:underline">Forgot your password?</a></p>
        </div>
    </div>
</section>
@endsection
