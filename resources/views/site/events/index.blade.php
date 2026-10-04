@extends('site.layout')
@section('title', 'Events | '.$settings->short_name)
@section('description', 'Published events from '.$settings->school_name.'.')
@section('content')
<x-page-hero eyebrow="School calendar" title="Events" lead="Upcoming activities and celebrations at our school." :image="$heroImage" :crumbs="[['Home', route('home')], ['News & Events', route('news.index')], ['Events', null]]" />
<section class="section"><div class="site-container">
    @if($events->isEmpty())
        <p class="empty-state">No events have been published yet. Please check back for school updates.</p>
    @else
        <div class="grid-2">
            @foreach($events as $event)
                <article class="card card--pad" style="display:flex;gap:1rem">
                    <span class="date-badge date-badge--navy"><strong>{{ $event->starts_at?->format('d') ?: '--' }}</strong><span>{{ $event->starts_at ? strtoupper($event->starts_at->format('M')) : 'TBA' }}</span></span>
                    <div>
                        <h2 class="h3"><a href="{{ route('events.show', $event->slug) }}" style="color:inherit;text-decoration:none">{{ $event->title }}</a></h2>
                        <ul class="event-row__meta">@if($event->starts_at)<li><x-site-icon name="calendar" />{{ $event->starts_at->format('M j, Y') }}@if($event->ends_at) &ndash; {{ $event->ends_at->format('M j, Y') }}@endif</li><li><x-site-icon name="clock" />{{ $event->starts_at->format('g:i A') }}</li>@endif @if($event->location)<li><x-site-icon name="pin" />{{ $event->location }}</li>@endif</ul>
                        <p class="muted" style="margin:.7rem 0 0;font-size:.92rem">{{ \Illuminate\Support\Str::limit((string) $event->description, 140) }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $events->links() }}</div>
    @endif
</div></section>
@endsection
