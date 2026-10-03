@extends('site.layout')
@section('title', 'Admissions | '.$settings->short_name)
@section('description', 'Admission information for Emmaculate Academy. Contact the school for current availability.')
@section('content')
    <section class="bg-white"><div class="site-container max-w-4xl py-12 sm:py-16"><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">Admissions</p><h1 class="mt-2 font-display text-4xl font-semibold text-[var(--brand-800)] sm:text-5xl">Join the Emmaculate community</h1>
        @if($admissions->image)<img src="{{ $admissions->image->url() }}" alt="{{ $admissions->image->alt_text ?: 'Emmaculate Academy admissions' }}" class="mt-6 max-h-[420px] w-full rounded-[var(--radius-lg)] object-cover" loading="lazy">@endif
        @if($admissions->intro)<p class="mt-5 text-lg leading-relaxed text-[var(--ink-soft)]">{{ $admissions->intro }}</p>@endif
        @if($admissions->eligibility)<section class="mt-8"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Eligibility</h2><p class="mt-2 leading-relaxed text-[var(--ink-soft)]">{{ $admissions->eligibility }}</p></section>@endif
        @if($admissions->process_steps)<section class="mt-8"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Application process</h2><ol class="mt-3 list-decimal space-y-2 pl-6 text-[var(--ink-soft)]">@foreach($admissions->process_steps as $step)<li>{{ $step }}</li>@endforeach</ol></section>@endif
        @if($admissions->requirements)<section class="mt-8"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Requirements</h2><ul class="mt-3 list-disc space-y-2 pl-6 text-[var(--ink-soft)]">@foreach($admissions->requirements as $requirement)<li>{{ $requirement }}</li>@endforeach</ul></section>@endif
        @if($admissions->important_dates)<section class="mt-8"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Important dates</h2><ul class="mt-3 space-y-2 text-[var(--ink-soft)]">@foreach($admissions->important_dates as $date)<li><strong>{{ $date['label'] ?? 'Date' }}</strong>@if(!empty($date['date'])) — {{ $date['date'] }}@endif</li>@endforeach</ul></section>@endif
        @if($admissions->screening_information)<section class="mt-8"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Screening information</h2><p class="mt-2 leading-relaxed text-[var(--ink-soft)]">{{ $admissions->screening_information }}</p></section>@endif
        @if($admissions->status === 'open')
            <section class="mt-10 rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface-alt)] p-6 sm:p-8"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Admission enquiry</h2><p class="mt-2 text-sm text-[var(--ink-soft)]">Please provide the applicant and guardian details below. The school will follow up with next steps.</p>
                <form action="{{ route('admissions.apply') }}" method="post" class="mt-6 grid gap-4 sm:grid-cols-2">@csrf
                    <label class="text-sm font-medium">Applicant name<input name="applicant_name" required maxlength="160" value="{{ old('applicant_name') }}" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5"></label>
                    <label class="text-sm font-medium">Applying for<input name="applying_for" required maxlength="120" value="{{ old('applying_for') }}" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5"></label>
                    <label class="text-sm font-medium">Date of birth<input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5"></label>
                    <label class="text-sm font-medium">Guardian name<input name="guardian_name" required maxlength="160" value="{{ old('guardian_name') }}" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5"></label>
                    <label class="text-sm font-medium">Guardian email<input name="guardian_email" type="email" maxlength="190" value="{{ old('guardian_email') }}" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5"></label>
                    <label class="text-sm font-medium">Guardian phone<input name="guardian_phone" required maxlength="40" value="{{ old('guardian_phone') }}" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5"></label>
                    <label class="text-sm font-medium sm:col-span-2">Message<textarea name="message" rows="4" maxlength="3000" class="mt-1 block w-full rounded-md border border-[var(--border)] px-3 py-2.5">{{ old('message') }}</textarea></label>
                    @if($errors->any())<div class="sm:col-span-2 rounded bg-red-50 p-3 text-sm text-red-800">Please review the form fields and try again.</div>@endif
                    <button class="w-fit rounded-md bg-[var(--brand-700)] px-5 py-3 text-sm font-semibold text-white">Submit enquiry</button>
                </form>
            </section>
        @else
            <div class="mt-9 rounded-lg border border-[var(--accent-soft)] bg-[var(--surface-alt)] p-6"><h2 class="font-semibold text-[var(--brand-800)]">Current availability: {{ str_replace('_', ' ', ucfirst($admissions->status)) }}</h2><p class="mt-2 text-sm leading-relaxed text-[var(--ink-soft)]">For current admission information, please contact the school.@if(empty($admissions->important_dates)) No application dates have been confirmed on this website.@endif</p>@php($ctaUrl = \App\Domain\Website\Support\SafePublicUrl::allows($admissions->cta_url) && $admissions->cta_url ? $admissions->cta_url : route('contact'))<a href="{{ $ctaUrl }}" class="mt-4 inline-flex rounded-md bg-[var(--brand-700)] px-5 py-3 text-sm font-semibold text-white">{{ $admissions->cta_label ?: 'Contact the School' }}</a></div>
        @endif
    </div></section>
@endsection
