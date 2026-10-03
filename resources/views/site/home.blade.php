@extends('site.layout')
@section('title', $settings->school_name.' — Determined to make a difference')
@section('description', $settings->description)
@section('content')
    @php($slide = $slides->first())
    @if($slide)
        @php($heroImage = $slide->image?->url() ?? ($slide->image_path ? asset('storage/'.$slide->image_path) : null))
        <section class="overflow-hidden bg-[var(--surface-alt)]">
            <div class="site-container grid items-center gap-8 py-14 lg:grid-cols-2 lg:gap-12 lg:py-20">
                <div>
                    @if($slide->eyebrow)<p class="mb-3 text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">{{ $slide->eyebrow }}</p>@endif
                    <h1 class="font-display text-4xl font-semibold leading-tight text-[var(--ink)] sm:text-5xl lg:text-6xl">{{ $slide->heading }}</h1>
                    @if($slide->body)<p class="mt-5 max-w-xl text-lg leading-relaxed text-[var(--ink-soft)]">{{ $slide->body }}</p>@endif
                    <div class="mt-8 flex flex-wrap gap-3">
                        @if($slide->cta_label && \App\Domain\Website\Support\SafePublicUrl::allows($slide->cta_url))<a class="rounded-md bg-[var(--brand-700)] px-5 py-3 text-sm font-semibold text-white hover:bg-[var(--brand-800)]" href="{{ $slide->cta_url }}">{{ $slide->cta_label }}</a>@endif
                        @if($slide->secondary_cta_label && \App\Domain\Website\Support\SafePublicUrl::allows($slide->secondary_cta_url))<a class="rounded-md border border-[var(--border)] bg-white px-5 py-3 text-sm font-semibold text-[var(--brand-800)] hover:bg-[var(--surface-alt)]" href="{{ $slide->secondary_cta_url }}">{{ $slide->secondary_cta_label }}</a>@endif
                    </div>
                </div>
                @if($heroImage)<div class="relative h-72 overflow-hidden rounded-[var(--radius-lg)] shadow-xl sm:h-96 lg:h-[420px]"><img src="{{ $heroImage }}" alt="{{ $slide->image_alt ?: $slide->heading }}" class="h-full w-full object-cover" fetchpriority="high"></div>@endif
            </div>
        </section>
    @endif

    @foreach($announcements as $announcement)
        <div class="border-y border-[var(--accent-soft)] bg-[var(--accent-soft)] px-4 py-3 text-center text-sm text-[var(--brand-900)]"><strong>{{ $announcement->title }}</strong>@if($announcement->body) — {{ $announcement->body }}@endif @if($announcement->link_url && \App\Domain\Website\Support\SafePublicUrl::allows($announcement->link_url))<a class="ml-2 font-semibold underline" href="{{ $announcement->link_url }}">{{ $announcement->link_label ?: 'Learn more' }}</a>@endif</div>
    @endforeach

    <section class="border-y border-[var(--border)] bg-white">
        <div class="site-container grid grid-cols-2 gap-3 py-6 sm:grid-cols-4">
            @foreach([['Student Portal','Student access'],['Parent Portal','Linked-child access'],['Teacher Portal','Assigned-class access'],['School Administration','Management tools']] as [$label,$description])
                <a href="{{ route('portal') }}" class="rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-4 py-4 transition hover:border-[var(--brand-200)] hover:bg-white"><span class="block font-semibold text-[var(--brand-800)]">{{ $label }}</span><span class="mt-1 block text-xs text-[var(--ink-soft)]">{{ $description }}</span></a>
            @endforeach
        </div>
    </section>

    <section class="site-container grid items-center gap-10 py-16 lg:grid-cols-2">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">About Us</p>
            <h2 class="mt-2 font-display text-3xl font-semibold text-[var(--brand-800)] sm:text-4xl">Welcome to Emmaculate Academy</h2>
            <p class="mt-5 leading-relaxed text-[var(--ink-soft)]">Emmaculate Academy, together with Emmaculate Day-care, Nursery and Primary School, forms the Emmaculate Group of Schools in Arigidi Akoko, Ondo State — carrying students from their earliest years through to secondary education under one continuous moral and academic foundation.</p>
            <p class="mt-4 leading-relaxed text-[var(--ink-soft)]">Guided by our vision of raising a morally upright generation of future leaders, we are determined to make a difference in every child we teach.</p>
            <a href="{{ route('pages.show', 'about') }}" class="mt-7 inline-flex rounded-md bg-[var(--surface-dark)] px-5 py-3 text-sm font-semibold text-white hover:bg-[var(--brand-800)]">Read More About Us</a>
        </div>
        <div class="overflow-hidden rounded-[var(--radius-lg)]"><img src="{{ asset('storage/migrated-images/students-group.jpg') }}" alt="A group of Emmaculate Academy students" class="h-72 w-full object-cover sm:h-96" loading="lazy"></div>
    </section>

    @if(count($stats))
        <section class="bg-[var(--brand-800)] text-white"><div class="site-container grid gap-4 py-10 sm:grid-cols-2 lg:grid-cols-4">@foreach($stats as $stat)<div class="text-center"><p class="font-display text-3xl font-semibold">{{ $stat['value'] ?? '' }}</p><p class="mt-1 text-sm text-white/80">{{ $stat['label'] ?? '' }}</p></div>@endforeach</div></section>
    @endif

    <section class="site-container py-16">
        <div class="mb-8 text-center"><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">Academics</p><h2 class="mt-2 font-display text-3xl font-semibold text-[var(--brand-800)] sm:text-4xl">Primary &amp; Secondary Education</h2></div>
        <div class="grid gap-6 sm:grid-cols-2">
            @foreach($programmes as $programme)
                @php($programmeImage = $programme->image?->url() ?? ($programme->image_path ? asset('storage/'.$programme->image_path) : null))
                <article class="overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-white shadow-sm">
                    @if($programmeImage)<img src="{{ $programmeImage }}" alt="{{ $programme->image?->alt_text ?: $programme->title }}" class="h-56 w-full object-cover" loading="lazy">@endif
                    <div class="p-6"><h3 class="font-display text-xl font-semibold text-[var(--brand-800)]">{{ $programme->title }}</h3><p class="mt-2 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $programme->intro }}</p><a href="{{ route('academics.programme', $programme->slug) }}" class="mt-4 inline-block rounded-md border border-[var(--border)] px-4 py-2 text-sm font-semibold text-[var(--brand-800)]">Explore {{ $programme->title }}</a></div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bg-white py-16"><div class="site-container">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">From the Academy</p><h2 class="mt-2 font-display text-3xl font-semibold text-[var(--brand-800)]">News &amp; Events</h2></div><a href="{{ route('news.index') }}" class="text-sm font-semibold text-[var(--brand-700)] underline underline-offset-4">View all updates</a></div>
        @if($news->isEmpty() && $events->isEmpty())<p class="mt-6 rounded-lg border border-dashed border-[var(--border)] p-6 text-[var(--ink-soft)]">News and events will appear here when the school publishes them.</p>@else
            <div class="mt-7 grid gap-6 md:grid-cols-3">@foreach($news as $post)<article class="rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] p-5"><p class="text-xs font-semibold uppercase tracking-wide text-[var(--brand-600)]">News · {{ $post->published_at?->format('M j, Y') }}</p><h3 class="mt-2 font-display text-xl font-semibold"><a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a></h3><p class="mt-2 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $post->excerpt }}</p></article>@endforeach @foreach($events as $event)<article class="rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] p-5"><p class="text-xs font-semibold uppercase tracking-wide text-[var(--brand-600)]">Event · {{ $event->starts_at?->format('M j, Y') ?: 'Date to be announced' }}</p><h3 class="mt-2 font-display text-xl font-semibold"><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h3><p class="mt-2 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $event->description }}</p></article>@endforeach</div>
        @endif
    </div></section>

    <section class="site-container py-16"><div class="mb-8 flex items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">Our People</p><h2 class="mt-2 font-display text-3xl font-semibold text-[var(--brand-800)]">Leadership</h2></div><a href="{{ route('leadership') }}" class="text-sm font-semibold text-[var(--brand-700)] underline underline-offset-4">Meet the team</a></div>
        @if($leaders->isEmpty())<p class="rounded-lg border border-dashed border-[var(--border)] p-6 text-[var(--ink-soft)]">Leadership profiles will be added here.</p>@else<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach($leaders as $leader)<article class="overflow-hidden rounded-lg border border-[var(--border)] bg-white">@if($leader->photo)<img src="{{ $leader->photo->url() }}" alt="{{ $leader->photo->alt_text ?: $leader->name }}" class="h-64 w-full object-cover" loading="lazy">@else<div class="flex h-64 items-center justify-center bg-[var(--surface-alt)] text-sm text-[var(--ink-soft)]">Photo not supplied</div>@endif<div class="p-5"><h3 class="font-display text-xl font-semibold text-[var(--brand-800)]">{{ $leader->name }}</h3><p class="mt-1 text-sm text-[var(--ink-soft)]">{{ $leader->title }}</p></div></article>@endforeach</div>@endif
    </section>

    <section class="bg-[var(--surface-alt)] py-16"><div class="site-container"><div class="mb-8 flex items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">Gallery</p><h2 class="mt-2 font-display text-3xl font-semibold text-[var(--brand-800)]">Life at Emmaculate</h2></div><a href="{{ route('gallery') }}" class="text-sm font-semibold text-[var(--brand-700)] underline underline-offset-4">View gallery</a></div><div class="grid gap-4 sm:grid-cols-3">@foreach($albums as $album)@php($cover = $album->coverImage ?? $album->images->first()?->media)@if($cover)<a href="{{ route('gallery') }}" class="group overflow-hidden rounded-lg"><img src="{{ $cover->url() }}" alt="{{ $cover->alt_text ?: $album->title }}" class="h-64 w-full object-cover transition duration-300 group-hover:scale-[1.02]" loading="lazy"><span class="mt-2 block font-semibold">{{ $album->title }}</span></a>@endif @endforeach</div></div></section>

    <section class="bg-[var(--brand-800)] px-4 py-14 text-center text-white"><div class="mx-auto max-w-3xl"><p class="text-sm font-semibold uppercase tracking-[.16em] text-white/75">Admissions</p><h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">Give your child a strong foundation</h2><p class="mt-4 text-white/80">Contact Emmaculate Academy to learn about admission opportunities and school information.</p><a href="{{ route('admissions') }}" class="mt-7 inline-flex rounded-md bg-white px-5 py-3 text-sm font-semibold text-[var(--brand-800)] hover:bg-[var(--accent-soft)]">Admissions Information</a></div></section>
@endsection
