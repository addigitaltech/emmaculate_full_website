@extends('site.layout')
@section('title', 'Check Result | '.$settings->short_name)
@section('description', 'Check a published result with an admission number and surname.')
@section('content')
<x-page-hero eyebrow="Results" title="Check Result" lead="Enter the admission number and the surname in CAPITAL LETTERS to view published results." :image="$heroImage" :crumbs="[['Home', route('home')], ['Check Result', null]]" />
<section class="section">
    <div class="site-container" style="max-width:44rem">
        @if(! $enabled)
            <div class="card card--pad"><h2 class="h3">Result checking is not available right now</h2><p class="muted">The school has not switched on this page. Please sign in to the portal or contact the school office.</p><p><a class="btn btn--maroon" href="{{ route('login') }}">Go to sign in</a></p></div>
        @elseif($student)
            <div class="card card--pad">
                <h2 class="h3">{{ $student->fullName() }}</h2>
                <p class="muted" style="margin:.2rem 0 1rem">{{ $student->student_number }} &middot; {{ $student->schoolClass?->name }}@if($student->arm) ({{ $student->arm->name }})@endif</p>
                <p style="margin:0 0 .6rem;font-weight:600">Choose a term to view and print:</p>
                <ul class="report-list">
                    @foreach($links as $link)
                        <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener"><span>{{ $link['term']->session?->name }} &middot; {{ $link['term']->name }}</span><span class="link-action link-action--blue" style="margin:0">View / print</span></a></li>
                    @endforeach
                </ul>
                <p class="muted" style="font-size:.82rem;margin:1rem 0 0">For your privacy these links stop working after 20 minutes. Look the result up again if needed.</p>
            </div>
            <p style="margin-top:1rem"><a class="link-more" href="{{ route('result-check') }}">Check another result</a></p>
        @else
            <div class="card card--pad">
                <form method="post" action="{{ route('result-check.lookup') }}" class="form-stack">
                    @csrf
                    <label class="field">Admission number<input name="admission_number" value="{{ old('admission_number') }}" required maxlength="60" autocomplete="off" autocapitalize="characters" class="upper-input"></label>
                    <label class="field">Surname (CAPITAL LETTERS)<input name="surname" required maxlength="80" autocomplete="off" autocapitalize="characters" class="upper-input"></label>
                    @error('admission_number')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                    @error('surname')<div class="alert alert--error" role="alert">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn--maroon btn--block">View result</button>
                </form>
                <p class="muted" style="font-size:.82rem;margin:1rem 0 0">Only results the school has published are shown. Having trouble? <a href="{{ route('contact') }}" style="text-decoration:underline">Contact the school</a>.</p>
            </div>
        @endif
    </div>
</section>
@endsection
