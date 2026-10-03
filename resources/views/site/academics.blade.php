@extends('site.layout')
@section('title', 'Academics | '.$settings->short_name)
@section('description', 'Explore Primary and Secondary education at Emmaculate Academy.')
@section('content')
    <section class="bg-white"><div class="site-container py-12 sm:py-16"><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">Academics</p><h1 class="mt-2 font-display text-4xl font-semibold text-[var(--brand-800)] sm:text-5xl">Primary &amp; Secondary Education</h1><p class="mt-5 max-w-3xl leading-relaxed text-[var(--ink-soft)]">A continuous moral and academic foundation, from the earliest years through secondary education.</p>
        <div class="mt-10 grid gap-6 md:grid-cols-2">
            @foreach($programmes as $programme)
                @php($image = $programme->image?->url() ?? ($programme->image_path ? asset('storage/'.$programme->image_path) : null))
                <article class="overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface-alt)]">
                    @if($image)<img src="{{ $image }}" alt="{{ $programme->image?->alt_text ?: $programme->title }}" class="h-64 w-full object-cover" loading="lazy">@endif
                    <div class="p-7"><p class="text-sm font-semibold uppercase tracking-wide text-[var(--brand-600)]">{{ ucfirst($programme->level) }}</p><h2 class="mt-2 font-display text-2xl font-semibold text-[var(--brand-800)]">{{ $programme->title }}</h2><p class="mt-3 leading-relaxed text-[var(--ink-soft)]">{{ $programme->intro }}</p><a class="mt-6 inline-flex rounded-md bg-[var(--brand-700)] px-5 py-3 text-sm font-semibold text-white" href="{{ route('academics.programme', $programme->slug) }}">Explore {{ $programme->title }}</a></div>
                </article>
            @endforeach
        </div>
    </div></section>
@endsection
