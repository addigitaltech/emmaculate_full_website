@extends('site.layout')
@section('title', ($seoTitle ?? $event->title).' | '.$settings->short_name)
@section('description', $seoDescription ?? $event->description)
@section('content')
<x-page-hero eyebrow="Event" :title="$event->title" :lead="$event->starts_at?->format('F j, Y \a\t g:i a')" :image="$heroImage" :crumbs="[['Home', route('home')], ['Events', route('events.index')], [\Illuminate\Support\Str::limit($event->title, 40), null]]" />
<section class="section"><article class="site-container article">
    @if($event->image)<div class="article__cover"><img src="{{ $event->image->url() }}" alt="{{ $event->image->alt_text ?: $event->title }}" decoding="async"></div>@endif
    <ul class="event-row__meta" style="font-size:.95rem;margin-bottom:1rem">@if($event->starts_at)<li><x-site-icon name="calendar" />{{ $event->starts_at->format('F j, Y · g:i a') }}@if($event->ends_at) &ndash; {{ $event->ends_at->format('F j, Y · g:i a') }}@endif</li>@endif @if($event->location)<li><x-site-icon name="pin" />{{ $event->location }}</li>@endif</ul>
    <div class="prose-school">@foreach(preg_split('/\R\s*\R/', (string) $event->description) as $paragraph)@if(trim($paragraph))<p>{{ $paragraph }}</p>@endif @endforeach</div>
    @if($event->registration_url && \App\Domain\Website\Support\SafePublicUrl::allows($event->registration_url))<p style="margin-top:1.5rem"><a class="btn btn--maroon" href="{{ $event->registration_url }}" target="_blank" rel="noopener noreferrer">Event information</a></p>@endif
    <p style="margin-top:1.5rem"><a class="btn btn--outline" href="{{ route('events.index') }}"><x-site-icon name="chevronLeft" /> All events</a></p>
</article></section>
@endsection
