@extends('site.layout')
@section('title', 'Announcements | '.$settings->short_name)
@section('description', 'Current official notices and announcements from '.$settings->school_name.'.')
@section('content')
<x-page-hero eyebrow="School notices" title="Announcements" lead="Official announcements currently in effect." :image="$heroImage" :crumbs="[['Home', route('home')], ['News & Events', route('news.index')], ['Announcements', null]]" />
<section class="section"><div class="site-container" style="max-width:56rem">
    @if($announcements->isEmpty())
        <p class="empty-state">There are no current announcements.</p>
    @else
        <div class="stack">
            @foreach($announcements as $announcement)
                <article class="card card--pad">
                    <h2 class="h3">{{ $announcement->title }}</h2>
                    @if($announcement->starts_at)<p class="muted" style="font-size:.8rem;margin:.25rem 0 0">{{ $announcement->starts_at->format('M j, Y') }}@if($announcement->expires_at) &ndash; {{ $announcement->expires_at->format('M j, Y') }}@endif</p>@endif
                    @if($announcement->image)<img src="{{ $announcement->image->url() }}" alt="{{ $announcement->image->alt_text ?: $announcement->title }}" style="margin-top:1rem;max-height:260px;border-radius:var(--radius)" loading="lazy">@endif
                    @if($announcement->body)<p class="muted" style="white-space:pre-line;margin-top:.8rem">{{ $announcement->body }}</p>@endif
                    @if($announcement->link_url && \App\Domain\Website\Support\SafePublicUrl::allows($announcement->link_url))<p style="margin-top:1rem"><a class="link-more" href="{{ $announcement->link_url }}">{{ $announcement->link_label ?: 'More information' }} <x-site-icon name="arrow" /></a></p>@endif
                </article>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $announcements->links() }}</div>
    @endif
</div></section>
@endsection
