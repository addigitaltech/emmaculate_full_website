@extends('site.layout')
@section('title', ($seoTitle ?? $programme->title).' | '.$settings->short_name)
@section('description', $seoDescription ?? $programme->intro)
@section('content')
    @php($image = $programme->image?->url() ?? ($programme->image_path ? asset('storage/'.$programme->image_path) : null))
    <section class="bg-white"><div class="site-container max-w-5xl py-12 sm:py-16">
        <a href="{{ route('academics') }}" class="text-sm font-semibold text-[var(--brand-700)] underline underline-offset-4">← All Academics</a>
        <p class="mt-7 text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">{{ ucfirst($programme->level) }}</p>
        <h1 class="mt-2 font-display text-4xl font-semibold text-[var(--brand-800)] sm:text-5xl">{{ $programme->title }}</h1>
        @if($image)<img src="{{ $image }}" alt="{{ $programme->image?->alt_text ?: $programme->title }}" class="mt-8 max-h-[480px] w-full rounded-[var(--radius-lg)] object-cover" loading="lazy">@endif
        <p class="mt-7 max-w-4xl text-lg leading-relaxed text-[var(--ink-soft)]">{{ $programme->intro }}</p>
        @if($programme->placeholder_note)<aside class="mt-8 rounded-lg border-l-4 border-[var(--accent)] bg-[var(--surface-alt)] p-5 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $programme->placeholder_note }}</aside>@endif
        @if($programme->approach)<ul class="mt-6 space-y-3">@foreach($programme->approach as $item)<li class="rounded-lg border border-[var(--border)] p-4 text-sm text-[var(--ink-soft)]">{{ $item }}</li>@endforeach</ul>@endif
    </div></section>
@endsection
