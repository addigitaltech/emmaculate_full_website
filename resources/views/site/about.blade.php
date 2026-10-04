@php
    $values = \App\Domain\Website\Support\SiteSections::items('about.values');
    $why = \App\Domain\Website\Support\SiteSections::items('about.why');
    $toneCycle = ['maroon', 'navy', 'maroon', 'navy', 'maroon', 'navy'];
@endphp
@extends('site.layout')
@section('title', ($seoTitle ?? 'About Us').' | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@section('content')
<x-page-hero title="About Us" :lead="$page?->excerpt ?: $settings->description" :image="$heroImage" :crumbs="[['Home', route('home')], ['About Us', null]]" />

<section class="section">
    <div class="site-container split">
        <div>
            <p class="eyebrow">Who we are</p>
            <h2 class="h2">Our School</h2>
            <span class="rule" aria-hidden="true"></span>
            <div class="stack muted">
                @forelse($paragraphs as $paragraph)<p>{{ $paragraph }}</p>@empty<p>{{ $settings->description }}</p>@endforelse
            </div>
            <p style="margin-top:1.5rem"><a class="btn btn--maroon" href="{{ route('history') }}">Learn more about us <x-site-icon name="arrow" /></a></p>
        </div>
        <div class="photo-frame" style="aspect-ratio:3/2"><img src="{{ $aboutImage }}" alt="Students of {{ $settings->school_name }} holding their textbooks" loading="lazy" decoding="async"></div>
    </div>
</section>

@if($mission || $vision)
<section class="section--tight">
    <div class="site-container mv">
        @if($mission)
            <article class="mv__card mv__card--maroon"><span class="mv__icon tone-maroon"><x-site-icon name="target" /></span><div><h2 class="mv__title tone-text-maroon">Our Mission</h2><span class="rule" aria-hidden="true" style="margin:.5rem 0"></span><p class="mv__text" style="margin:0">{{ $mission }}</p></div></article>
        @endif
        @if($vision)
            <article class="mv__card mv__card--navy"><span class="mv__icon tone-navy"><x-site-icon name="eye" /></span><div><h2 class="mv__title tone-text-navy">Our Vision</h2><span class="rule" aria-hidden="true" style="margin:.5rem 0"></span><p class="mv__text" style="margin:0">{{ $vision }}</p></div></article>
        @endif
    </div>
</section>
@endif

@if(count($values))
<section class="section">
    <div class="site-container">
        <h2 class="section-title">{{ \App\Domain\Website\Support\SiteSections::title('about.values', 'Our Core Values') }}</h2>
        <div class="features features--ruled">
            @foreach($values as $value)
                <div class="feature">
                    <span class="feature__icon tone-{{ $toneCycle[$loop->index % 6] }}"><x-site-icon :name="$value['icon'] ?? 'star'" /></span>
                    <h3 class="feature__title tone-text-{{ $toneCycle[$loop->index % 6] }}">{{ $value['title'] ?? '' }}</h3>
                    <p class="feature__text">{{ $value['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($leaders->isNotEmpty())
<section class="section--tight">
    <div class="site-container">
        <h2 class="section-title">Our Leadership</h2>
        <div class="leaders">
            @foreach($leaders as $leader)
                @php
                    $photo = $leader->photo?->url() ?? ($leader->photo_path ? asset('storage/'.$leader->photo_path) : null);
                @endphp
                <article class="leader">
                    <div class="leader__photo">@if($photo)<img src="{{ $photo }}" alt="{{ $leader->name }}, {{ $leader->title }}" loading="lazy" decoding="async">@endif</div>
                    <div class="leader__body">
                        <p class="leader__role">{{ $leader->title }}</p>
                        <span class="rule" aria-hidden="true" style="margin:.5rem 0 0"></span>
                        <h3 class="leader__name">{{ $leader->name }}</h3>
                        @if($leader->biography)<p class="leader__bio">{{ \Illuminate\Support\Str::limit($leader->biography, 160) }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
        <p style="margin-top:1.1rem;text-align:center"><a class="link-more" href="{{ route('leadership') }}">Meet the full team <x-site-icon name="arrow" /></a></p>
    </div>
</section>
@endif

@if(count($why))
<section class="section">
    <div class="site-container">
        <h2 class="section-title">{{ \App\Domain\Website\Support\SiteSections::title('about.why', 'Why Choose '.$settings->school_name.'?') }}</h2>
        <div class="why">
            @foreach($why as $item)
                <div class="why__item">
                    <span class="why__icon tone-{{ $toneCycle[$loop->index % 6] }}"><x-site-icon :name="$item['icon'] ?? 'star'" /></span>
                    <div><h3 class="why__title tone-text-{{ $toneCycle[$loop->index % 6] }}">{{ $item['title'] ?? '' }}</h3><p class="why__text">{{ $item['text'] ?? '' }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section--tight">
    <div class="site-container">
        <div class="band band--maroon">
            <div class="band__lead"><span class="band__icon"><x-site-icon name="cap" /></span><div><h2 class="band__title">{{ \App\Domain\Website\Support\SiteSections::title('cta.admissions', 'Give Your Child a Stronger Foundation') }}</h2><p class="band__text">{{ \App\Domain\Website\Support\SiteSections::description('cta.admissions', 'Join '.$settings->school_name.' today and experience the difference.') }}</p></div></div>
            <a class="btn btn--gold" href="{{ route('admissions') }}">Apply now <x-site-icon name="arrow" /></a>
        </div>
    </div>
</section>
@endsection
