@extends('site.layout')
@section('title', 'School Portal | '.$settings->short_name)
@section('description', 'Secure portal access for Emmaculate Academy students, parents, teachers and administrators.')
@section('content')
    <section class="bg-white"><div class="site-container max-w-5xl py-12 sm:py-16"><p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">School Portal</p><h1 class="mt-2 font-display text-4xl font-semibold text-[var(--brand-800)] sm:text-5xl">One secure school account</h1><p class="mt-5 max-w-3xl leading-relaxed text-[var(--ink-soft)]">Sign in with an account provided by the school. Students see their own published results, parents see linked children, and teachers see assigned classes and subjects.</p>
        <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($portalLinks as $portalLink)
                <article class="flex flex-col rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-display text-lg font-semibold text-[var(--brand-800)]">{{ $portalLink->title }}</h2>
                        @if($portalLink->isLive())
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Live</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">Coming Soon</span>
                        @endif
                    </div>
                    @if($portalLink->description)<p class="mt-2 flex-1 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $portalLink->description }}</p>@endif
                    @if($portalLink->isLive())
                        <a href="{{ $portalLink->url }}" class="mt-5 inline-flex w-fit rounded-md bg-[var(--brand-700)] px-4 py-2 text-sm font-semibold text-white">Open portal</a>
                    @else
                        <span class="mt-5 inline-flex w-fit cursor-not-allowed rounded-md border border-[var(--border)] px-4 py-2 text-sm font-semibold text-[var(--ink-soft)]" aria-disabled="true">Coming Soon</span>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-[var(--border)] p-5 text-sm text-[var(--ink-soft)]">Portal access information is being prepared. Please contact the school office.</p>
            @endforelse
        </div>
        <div class="mt-8 rounded-lg border border-[var(--accent-soft)] bg-[var(--surface-alt)] p-5 text-sm text-[var(--ink-soft)]">No public result-checking tokens or production student accounts have been imported. Please use the secure sign-in supplied by the school.</div>
    </div></section>
@endsection
