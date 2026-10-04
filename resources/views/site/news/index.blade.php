@extends('site.layout')
@section('title', 'News & Events | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@section('content')
<x-page-hero title="News & Events" lead="Stay updated with the latest happenings, achievements and upcoming events at our school." :image="$heroImage" :crumbs="[['Home', route('home')], ['News & Events', null]]" />
<section class="section">
    <div class="site-container">
        <h2 class="section-title" style="margin-bottom:1.6rem">Latest News</h2>
        <div class="layout-news">
            <div>
                @if($posts->isEmpty())
                    <p class="empty-state">There are no published news stories yet. Please check back later.</p>
                @else
                    <div class="news-list">
                        @foreach($posts as $post)
                            @php
                                $cover = $post->coverImage?->url();
                            @endphp
                            <article class="news-card">
                                <div class="news-card__media">
                                    @if($cover)<img src="{{ $cover }}" alt="{{ $post->coverImage->alt_text ?: $post->title }}" loading="lazy" decoding="async">@endif
                                    @if($post->published_at)<span class="date-badge"><strong>{{ $post->published_at->format('d') }}</strong><span>{{ $post->published_at->format('M') }}</span></span>@endif
                                </div>
                                <div class="news-card__body">
                                    <span class="chip">{{ $post->category ?: 'News' }}</span>
                                    <h3 class="news-card__title"><a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a></h3>
                                    @if($post->excerpt)<p class="news-card__excerpt">{{ $post->excerpt }}</p>@endif
                                    <a class="link-more" href="{{ route('news.show', $post->slug) }}">Read more <x-site-icon name="arrow" /></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="pagination-wrap">{{ $posts->links() }}</div>
                @endif
            </div>

            <aside>
                <section class="side-panel" aria-labelledby="upcoming-heading">
                    <div class="side-panel__head"><h2 id="upcoming-heading">Upcoming Events</h2><a href="{{ route('events.index') }}">View all</a></div>
                    <div class="side-panel__body">
                        @forelse($events as $event)
                            <a class="event-row" href="{{ route('events.show', $event->slug) }}">
                                <span class="date-badge date-badge--navy"><strong>{{ $event->starts_at?->format('d') ?: '--' }}</strong><span>{{ $event->starts_at ? strtoupper($event->starts_at->format('M')) : 'TBA' }}</span></span>
                                <span><span class="event-row__title" style="display:block">{{ $event->title }}</span>
                                    <ul class="event-row__meta">@if($event->starts_at)<li><x-site-icon name="calendar" />{{ $event->starts_at->format('M j, Y') }}</li><li><x-site-icon name="clock" />{{ $event->starts_at->format('g:i A') }}</li>@endif @if($event->location)<li><x-site-icon name="pin" />{{ $event->location }}</li>@endif</ul></span>
                            </a>
                        @empty
                            <p class="muted" style="padding:1rem 0">No upcoming events have been announced yet.</p>
                        @endforelse
                    </div>
                </section>

                @if(count($highlights))
                <section class="side-panel" aria-labelledby="highlights-heading">
                    <div class="side-panel__head"><h2 id="highlights-heading">Gallery Highlights</h2><a href="{{ route('gallery') }}">View all</a></div>
                    <div class="side-panel__body"><div class="thumbs">@foreach($highlights as $photo)<img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async">@endforeach</div></div>
                </section>
                @endif

                @if(count($portalLinks))
                <section class="side-panel" aria-labelledby="quick-heading">
                    <div class="side-panel__head"><h2 id="quick-heading">Quick Links</h2></div>
                    <div class="side-panel__body">
                        <ul class="link-list">
                            @foreach($portalLinks->take(4) as $portalLink)
                                @if($portalLink->isLive())<li><a href="{{ $portalLink->url }}"><x-site-icon name="users" /><span>{{ $portalLink->title }}</span><x-site-icon name="chevronRight" /></a></li>@endif
                            @endforeach
                        </ul>
                    </div>
                </section>
                @endif
            </aside>
        </div>
    </div>
</section>
<section class="section--tight">
    <div class="site-container">
        <div class="newsletter-band">
            <div class="newsletter-band__lead"><span class="newsletter-band__icon"><x-site-icon name="mail" /></span><div><h2>Subscribe to Our Newsletter</h2><p>Get the latest news, events and updates from {{ $settings->school_name }}.</p></div></div>
            @include('site.partials.newsletter-form', ['class' => 'inline-form', 'inputId' => 'news-newsletter', 'placeholder' => 'Enter your email address', 'buttonClass' => 'btn--navy'])
        </div>
    </div>
</section>
@endsection
