@extends('site.layout')
@section('title', 'Announcements | '.$settings->short_name)
@section('description', 'Current official notices and announcements from '.$settings->school_name.'.')
@section('content')
<section class="bg-white"><div class="site-container max-w-5xl py-12 sm:py-16">
    <p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">School notices</p>
    <h1 class="mt-2 font-display text-4xl font-semibold text-[var(--brand-800)] sm:text-5xl">Announcements</h1>
    <p class="mt-4 max-w-3xl text-[var(--ink-soft)]">Official announcements currently in effect.</p>
    @if($announcements->isEmpty())
        <p class="mt-8 rounded-lg border border-dashed border-[var(--border)] p-6 text-sm text-[var(--ink-soft)]">There are no current announcements.</p>
    @else
        <div class="mt-8 space-y-5">
            @foreach($announcements as $announcement)
                <article class="grid gap-5 rounded-xl border border-[var(--border)] bg-[var(--surface-alt)] p-5 sm:grid-cols-[180px_1fr] sm:p-6">
                    @if($announcement->image)<img src="{{ $announcement->image->url() }}" alt="{{ $announcement->image->alt_text ?: $announcement->title }}" class="h-44 w-full rounded-lg object-cover sm:h-36">@endif
                    <div><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">{{ $announcement->title }}</h2>
                        @if($announcement->starts_at)<p class="mt-1 text-xs text-[var(--ink-soft)]">{{ $announcement->starts_at->format('M j, Y') }}@if($announcement->expires_at) – {{ $announcement->expires_at->format('M j, Y') }}@endif</p>@endif
                        @if($announcement->body)<p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-[var(--ink-soft)]">{{ $announcement->body }}</p>@endif
                        @if($announcement->link_url && \App\Domain\Website\Support\SafePublicUrl::allows($announcement->link_url))<a class="mt-4 inline-flex font-semibold text-[var(--brand-700)] underline underline-offset-4" href="{{ $announcement->link_url }}">{{ $announcement->link_label ?: 'More information' }}</a>@endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $announcements->links() }}</div>
    @endif
</div></section>
@endsection
