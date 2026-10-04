@php
    $stepTones = ['maroon', 'navy', 'gold', 'green', 'maroon'];
    $isOpen = $admissions->status === 'open';
    $applyUrl = $isOpen ? '#apply' : route('contact');
@endphp
@extends('site.layout')
@section('title', 'Admissions | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@section('content')
<x-page-hero eyebrow="Admissions" title="The Future Begins Here" lead="We welcome students into a nurturing environment where excellence, character and leadership are shaped for life." :image="$heroImage" :crumbs="[['Home', route('home')], ['Admissions', null]]">
    <a class="btn btn--maroon" href="{{ $applyUrl }}">{{ $isOpen ? 'Apply now' : ($admissions->cta_label ?: 'Contact the school') }} <x-site-icon name="arrow" /></a>
</x-page-hero>

@if(count($steps))
<section class="section">
    <div class="site-container">
        <h2 class="section-title">Our Admission Process</h2>
        @if($admissions->intro)<p class="section-lead">{{ $admissions->intro }}</p>@endif
        <ol class="steps" style="list-style:none;padding:0;--step-cols: {{ min(5, count($steps)) }}">
            @foreach($steps as $step)
                <li class="step">
                    <span class="step__icon tone-{{ $stepTones[$loop->index % 5] }}"><x-site-icon :name="$step['icon'] ?? 'checkCircle'" /></span>
                    <h3 class="step__title">{{ $loop->iteration }}. {{ $step['title'] ?? '' }}</h3>
                    @if(!empty($step['text']))<p class="step__text">{{ $step['text'] }}</p>@endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endif

@if($admissions->eligibility || count($admissions->requirements ?? []) || count($levels))
<section class="section--tight">
    <div class="site-container grid-2" style="align-items:start">
        @if($admissions->eligibility || count($admissions->requirements ?? []))
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.4rem">Admission Requirements</h2><span class="rule" aria-hidden="true"></span>
            @if($admissions->eligibility)<p class="muted">{{ $admissions->eligibility }}</p>@endif
            @if(count($admissions->requirements ?? []))
                <ul class="checklist">@foreach($admissions->requirements as $requirement)<li><x-site-icon name="checkCircle" /><span>{{ $requirement }}</span></li>@endforeach</ul>
            @endif
            @if($admissions->screening_information)<aside class="note-bar" style="margin-top:1.2rem;font-size:.9rem"><x-site-icon name="info" /><span>{{ $admissions->screening_information }}</span></aside>@endif
        </div>
        @endif
        @if(count($levels))
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.4rem">{{ \App\Domain\Website\Support\SiteSections::title('admissions.levels', 'Academic Levels') }}</h2><span class="rule" aria-hidden="true"></span>
            <ul class="levels">
                @foreach($levels as $level)
                    @php
                        $levelImage = !empty($level['media_id']) && isset($levelMedia[$level['media_id']]) ? $levelMedia[$level['media_id']]->url() : null;
                        $levelUrl = !empty($level['url']) && \App\Domain\Website\Support\SafePublicUrl::allows($level['url']) ? $level['url'] : null;
                    @endphp
                    <li>
                        @if($levelUrl)<a class="level" href="{{ $levelUrl }}">@else<div class="level">@endif
                            @if($levelImage)<span class="level__img"><img src="{{ $levelImage }}" alt="" loading="lazy" decoding="async"></span>@endif
                            <span class="level__body"><span class="level__title" style="display:block">{{ $level['title'] ?? '' }}</span><span class="level__text" style="display:block">{{ $level['text'] ?? '' }}</span></span>
                            @if($levelUrl)<x-site-icon name="chevronRight" />@endif
                        @if($levelUrl)</a>@else</div>@endif
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</section>
@endif

@if(count($admissions->important_dates ?? []))
<section class="section--tight"><div class="site-container"><div class="card card--pad"><h2 class="h3" style="font-size:1.4rem">Important dates</h2><span class="rule" aria-hidden="true"></span>
    <ul class="checklist">@foreach($admissions->important_dates as $date)<li><x-site-icon name="calendar" /><span><strong>{{ $date['label'] ?? 'Date' }}</strong>@if(!empty($date['date'])) &mdash; {{ $date['date'] }}@endif</span></li>@endforeach</ul>
</div></div></section>
@endif

<section class="section--tight">
    <div class="site-container">
        <div class="band band--image band--navy">
            <div class="band__bg"><img src="{{ asset('storage/migrated-images/students-reading.jpg') }}" alt="" loading="lazy" decoding="async"></div>
            <div class="band__lead"><span class="band__icon"><x-site-icon name="cap" /></span><div><h2 class="band__title">{{ \App\Domain\Website\Support\SiteSections::title('cta.join', 'Ready to Join Our Family?') }}</h2><p class="band__text">{{ \App\Domain\Website\Support\SiteSections::description('cta.join', 'Give your child the best start for a successful future.') }}</p></div></div>
            <div class="band__actions"><a class="btn btn--maroon" href="{{ $applyUrl }}">{{ $isOpen ? 'Apply now' : 'Contact us' }} <x-site-icon name="arrow" /></a></div>
        </div>
    </div>
</section>

@if($isOpen)
<section class="section" id="apply">
    <div class="site-container" style="max-width:52rem">
        <h2 class="section-title">Apply Online</h2>
        <form method="post" action="{{ route('admissions.apply') }}" class="card card--pad" style="margin-top:1.4rem">
            @csrf
            <div class="form-grid form-grid--2">
                <label class="field">Applicant's full name<input name="applicant_name" value="{{ old('applicant_name') }}" required maxlength="160" autocomplete="off">@error('applicant_name')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field">Date of birth<input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}">@error('date_of_birth')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field span-2">Applying for (class / level)<input name="applying_for" value="{{ old('applying_for') }}" required maxlength="120">@error('applying_for')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field">Parent / guardian name<input name="guardian_name" value="{{ old('guardian_name') }}" required maxlength="160" autocomplete="name">@error('guardian_name')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field">Parent / guardian phone<input name="guardian_phone" type="tel" value="{{ old('guardian_phone') }}" required maxlength="40" autocomplete="tel">@error('guardian_phone')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field span-2">Parent / guardian email (optional)<input name="guardian_email" type="email" value="{{ old('guardian_email') }}" maxlength="190" autocomplete="email">@error('guardian_email')<span class="field__error">{{ $message }}</span>@enderror</label>
                <label class="field span-2">Anything else we should know? (optional)<textarea name="message" rows="4" maxlength="3000">{{ old('message') }}</textarea></label>
            </div>
            <p style="margin:1.2rem 0 0"><button type="submit" class="btn btn--maroon">Submit application</button></p>
            <p class="muted" style="font-size:.8rem;margin-top:.8rem">Do not include identity documents or sensitive information here. The school will contact you with the next steps.</p>
        </form>
    </div>
</section>
@endif

@if($faqs->isNotEmpty() || $settings->phone_primary || $settings->email)
<section class="section--tight">
    <div class="site-container grid-2" style="align-items:start">
        @if($faqs->isNotEmpty())
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.4rem">Frequently Asked Questions</h2><span class="rule" aria-hidden="true"></span>
            <div class="faq">@foreach($faqs as $faq)<details><summary>{{ $faq->question }} <x-site-icon name="chevron" /></summary><div>{{ $faq->answer }}</div></details>@endforeach</div>
            <p style="margin-top:1.2rem"><a class="btn btn--outline btn--sm" href="{{ route('faq') }}">View all FAQs <x-site-icon name="arrow" /></a></p>
        </div>
        @endif
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.4rem">Need Help?</h2><span class="rule" aria-hidden="true"></span>
            <p class="muted">Our admissions team is here to assist you.</p>
            @if($settings->phone_primary)<div class="help-line"><x-site-icon name="phone" /><span><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_primary) }}">{{ $settings->phone_primary }}</a>@if($settings->phone_secondary)<br><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_secondary) }}">{{ $settings->phone_secondary }}</a>@endif</span></div>@endif
            @if($settings->email)<div class="help-line"><x-site-icon name="mail" /><a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></div>@endif
            @if($settings->office_hours)<div class="help-line"><x-site-icon name="clock" /><span>{{ $settings->office_hours }}</span></div>@endif
            <div class="help-line"><x-site-icon name="send" /><a href="{{ route('contact') }}">Send an enquiry online</a></div>
        </div>
    </div>
</section>
@endif
@endsection
