@extends('site.layout')
@section('title', 'Student Portal | '.$settings->short_name)
@section('content')
<x-page-hero eyebrow="Student Portal" title="Student sign-in" :lead="\App\Domain\Auth\Support\PortalAccess::studentPinRequired() ? 'Use your admission number, your surname and the PIN on your PIN slip. No email or password needed.' : 'Use your admission number and your surname. No email or password needed.'" :image="asset('storage/migrated-images/students-group.jpg')" :crumbs="[['Home', route('home')], ['Student Portal', null]]" />
<section class="section">
    <div class="site-container" style="max-width:34rem">
        <div class="card card--pad auth-card">
            <form method="post" action="{{ route('student.login') }}" class="form-stack">
                @csrf
                <label class="field">Admission number<input name="admission_number" value="{{ old('admission_number') }}" required maxlength="60" autocomplete="off" autocapitalize="characters" class="upper-input"></label>
                <label class="field">Surname (CAPITAL LETTERS)<input name="surname" required maxlength="80" autocomplete="off" autocapitalize="characters" class="upper-input"></label>
                @if(\App\Domain\Auth\Support\PortalAccess::studentPinRequired())
                <label class="field">PIN (6 digits from your PIN slip)<input name="pin" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="off"></label>
                @endif
                @error('admission_number')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                @error('surname')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                @error('pin')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn--maroon btn--block">Sign in</button>
            </form>
            <p class="muted" style="font-size:.85rem;margin:1rem 0 0">Cannot sign in? Ask the school office to confirm your admission number and surname, or to give you a new PIN slip.</p>
        </div>
    </div>
</section>
@endsection
