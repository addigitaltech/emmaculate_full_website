@extends('site.layout')
@section('title', 'Change password | '.$settings->short_name)
@section('content')
<x-page-hero eyebrow="Account" title="Change password" lead="Choose a new password for your account. Forgotten your current one? Sign out and use Forgot password on the sign-in page; a reset link is sent to your email." />
<section class="section">
    <div class="site-container" style="max-width:34rem">
        <div class="card card--pad auth-card">
            <form method="post" action="{{ route('account.password.update') }}" class="form-stack">
                @csrf
                <label class="field">Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
                @error('current_password')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                <label class="field">New password<input type="password" name="password" required autocomplete="new-password" minlength="12"></label>
                <label class="field">Confirm new password<input type="password" name="password_confirmation" required autocomplete="new-password" minlength="12"></label>
                @error('password')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                <p class="muted" style="font-size:.85rem;margin:0">At least 12 characters, with upper and lower case letters, a number and a symbol.</p>
                <button type="submit" class="btn btn--maroon btn--block">Change password</button>
                <a href="{{ route('portal.dashboard') }}" class="muted" style="text-align:center;font-size:.9rem">Back to dashboard</a>
            </form>
        </div>
    </div>
</section>
@endsection
