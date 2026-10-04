@extends('site.layout')
@section('title', $settings->school_name.' | '.($settings->motto ?: 'Home'))
@section('description', $seoDescription ?? $settings->description)
@section('content')
@if($announcements->isNotEmpty())
    <section class="note-bar-wrap" aria-label="Announcements" style="background:var(--cream);border-bottom:1px solid #f4d894">
        <div class="site-container" style="padding-block:.55rem">
            @foreach($announcements->take(1) as $announcement)
                <p style="margin:0;font-size:.92rem;color:#4a3a12"><strong>{{ $announcement->title }}</strong> &mdash; {{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->body), 150) }} <a class="link-more" style="margin-left:.4rem" href="{{ route('announcements') }}">All announcements</a></p>
            @endforeach
        </div>
    </section>
@endif

<section class="hero" data-hero aria-roledescription="carousel" aria-label="Featured">
    <div class="hero__slides">
        @forelse($slides as $slide)
            @php
                $heroImage = $slide->image?->url() ?? ($slide->image_path ? asset('storage/'.$slide->image_path) : null);
            @endphp
            <div class="hero__slide{{ $loop->first ? ' is-active' : '' }}" data-hero-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                @if($heroImage)<div class="hero__media"><img src="{{ $heroImage }}" alt="{{ $slide->image_alt }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif decoding="async"></div>@endif
                <div class="site-container">
                    <div class="hero__body">
                        @if($slide->eyebrow)<p class="hero__eyebrow">{{ $slide->eyebrow }}</p>@endif
                        @if($loop->first)<h1 class="hero__title">{{ $slide->heading }}</h1>@else<h2 class="hero__title">{{ $slide->heading }}</h2>@endif
                        @if($slide->body)<p class="hero__lead">{{ $slide->body }}</p>@endif
                        @if($slide->cta_label && $slide->cta_url)
                            <div class="hero__actions">
                                <a class="btn btn--maroon" href="{{ $slide->cta_url }}">{{ $slide->cta_label }} <x-site-icon name="arrow" /></a>
                                @if($slide->secondary_cta_label && $slide->secondary_cta_url)<a class="btn btn--ghost-light" href="{{ $slide->secondary_cta_url }}">{{ $slide->secondary_cta_label }}</a>@endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="hero__slide is-active" data-hero-slide>
                <div class="hero__media"><img src="{{ asset('storage/migrated-images/textbooks.jpg') }}" alt="" fetchpriority="high"></div>
                <div class="site-container"><div class="hero__body">
                    <h1 class="hero__title">{{ $settings->school_name }}</h1>
                    @if($settings->motto)<p class="hero__lead">{{ $settings->motto }}</p>@endif
                </div></div>
            </div>
        @endforelse
    </div>
    @if(count($slides) > 1)
        <div class="hero__dots">
            @foreach($slides as $slide)
                <button type="button" class="hero__dot" data-hero-dot aria-label="Show slide {{ $loop->iteration }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    @endif
</section>

@if(count($portalLinks))
    @php
        $quickMap = ['results' => ['navy', 'fileChart'], 'admission' => ['maroon', 'userPlus'], 'students' => ['navy', 'book'], 'staff' => ['gold', 'briefcase'], 'parents' => ['green', 'users'], 'teachers' => ['gold', 'briefcase'], 'school-staff' => ['gold', 'briefcase']];
        $tones = ['navy', 'maroon', 'navy', 'gold', 'green'];
        $quickLinks = $portalLinks->take(5);
    @endphp
    <section class="quickbar" aria-label="Portals">
        <div class="site-container">
            <div class="quickbar__card" style="--quick-cols: {{ count($quickLinks) }}">
                @foreach($quickLinks as $portalLink)
                    @php
                        $map = $quickMap[$portalLink->key] ?? [$tones[$loop->index % 5], 'users'];
                    @endphp
                    @if($portalLink->isLive())
                        <a class="quickbar__item" href="{{ $portalLink->url }}">
                    @else
                        <div class="quickbar__item" aria-disabled="true">
                    @endif
                        <span class="quickbar__icon tone-{{ $map[0] }}"><x-site-icon :name="$map[1]" /></span>
                        <span class="quickbar__text"><span class="quickbar__title">{{ $portalLink->title }}</span><span class="quickbar__desc">{{ $portalLink->isLive() ? \Illuminate\Support\Str::limit((string) $portalLink->description, 44) : 'Coming soon' }}</span></span>
                        @if($portalLink->isLive())<x-site-icon name="arrow" class="quickbar__arrow" />@endif
                    @if($portalLink->isLive())
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="section">
    <div class="site-container split">
        <div>
            <p class="eyebrow">About us</p>
            <h2 class="h2">Welcome to<br>{{ $settings->school_name }}</h2>
            <span class="rule" aria-hidden="true"></span>
            <div class="stack muted">
                @forelse($aboutParagraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @empty
                    <p>{{ $settings->description }}</p>
                @endforelse
            </div>
            <p style="margin-top:1.5rem"><a class="btn btn--navy" href="{{ route('about') }}">Read more about us <x-site-icon name="arrow" /></a></p>
        </div>
        <div class="photo-frame" style="aspect-ratio:3/2"><img src="{{ $aboutImage }}" alt="Students of {{ $settings->school_name }} reading in front of the school building" loading="lazy" decoding="async"></div>
    </div>
</section>

@if(!empty($stats))
    <section class="section--tight">
            <div class="site-container">
                <div class="stats">
                @php
                    $statIcons = ['cap', 'users', 'user', 'building', 'award'];
                @endphp
                @foreach($stats as $label => $value)
                    <div class="stats__item"><x-site-icon :name="$statIcons[$loop->index % 5]" class="stats__icon" /><span class="stats__value">{{ $value }}</span><span class="stats__label">{{ $label }}</span></div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($programmes->isNotEmpty())
    <section class="section">
        <div class="site-container">
            <h2 class="section-title">Our Academic Programs</h2>
            <div class="programmes">
                @foreach($programmes as $programme)
                    @php
                        $programmeImage = $programme->image?->url() ?? ($programme->image_path ? asset('storage/'.$programme->image_path) : null);
                    @endphp
                    <article class="programme">
                        @if($programmeImage)<div class="programme__img"><img src="{{ $programmeImage }}" alt="" loading="lazy" decoding="async"></div>@endif
                        <div class="programme__body">
                            <h3 class="h3">{{ $programme->title }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit((string) $programme->intro, 110) }}</p>
                            <a class="link-more" href="{{ route('academics.programme', $programme->slug) }}">Learn more <x-site-icon name="arrow" /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="section">
    <div class="site-container grid-2" style="align-items:start">
        <div class="card card--pad">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem"><h2 class="h2" style="font-size:1.5rem">News &amp; Events</h2><a class="link-more" href="{{ route('news.index') }}">View all</a></div>
                @forelse($news as $post)
                @php
                    $cover = $post->coverImage?->url();
                @endphp
                <a href="{{ route('news.show', $post->slug) }}" class="level" style="align-items:flex-start">
                    @if($cover)<span class="level__img" style="width:120px;height:80px"><img src="{{ $cover }}" alt="" loading="lazy" decoding="async"></span>@endif
                    <span class="level__body">
                        <span class="muted" style="font-size:.78rem">{{ $post->published_at?->format('M j, Y') }}</span>
                        <span class="level__title" style="display:block;font-family:var(--font-sans);font-size:.95rem;font-weight:700">{{ $post->title }}</span>
                        <span class="level__text" style="display:block">{{ \Illuminate\Support\Str::limit((string) $post->excerpt, 90) }}</span>
                    </span>
                </a>
            @empty
                <p class="empty-state" style="margin-top:1rem">No news has been published yet. Please check back soon.</p>
            @endforelse
        </div>
        <div class="card card--pad">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem"><h2 class="h2" style="font-size:1.5rem">Upcoming Events</h2><a class="link-more" href="{{ route('events.index') }}">View all</a></div>
            @forelse($events as $event)
                <a class="event-row" href="{{ route('events.show', $event->slug) }}">
                    <span class="date-badge date-badge--navy"><strong>{{ $event->starts_at?->format('d') ?: '--' }}</strong><span>{{ $event->starts_at ? strtoupper($event->starts_at->format('M')) : 'TBA' }}</span></span>
                    <span>
                        <span class="event-row__title" style="display:block">{{ $event->title }}</span>
                        <ul class="event-row__meta">
                            @if($event->starts_at)<li><x-site-icon name="calendar" />{{ $event->starts_at->format('M j, Y') }}</li><li><x-site-icon name="clock" />{{ $event->starts_at->format('g:i A') }}</li>@endif
                            @if($event->location)<li><x-site-icon name="pin" />{{ $event->location }}</li>@endif
                        </ul>
                    </span>
                </a>
            @empty
                <p class="empty-state" style="margin-top:1rem">No upcoming events have been announced yet.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
