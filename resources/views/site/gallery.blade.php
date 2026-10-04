@php
    $catIcons = ['academics' => 'monitor', 'school-life' => 'users', 'events' => 'calendar', 'sports' => 'trophy', 'campus' => 'building', 'facilities' => 'building'];
    $strip = array_slice($photos, 0, 4);
@endphp
@extends('site.layout')
@section('title', 'Gallery | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@section('content')
<x-page-hero title="Gallery" lead="Moments that reflect learning, growth, excellence and school spirit." :image="$heroImage" :crumbs="[['Home', route('home')], ['Gallery', null]]" />

<section class="section" data-gallery>
    <div class="site-container">
        @if(count($photos) === 0)
            <p class="empty-state">Photographs will be added to the gallery soon.</p>
        @else
            <h2 class="section-title">Gallery Categories</h2>
            <div class="filters" role="group" aria-label="Filter photos by category">
                <button type="button" class="filter" data-filter="all" aria-pressed="true"><x-site-icon name="image" /><span>All Photos</span><small>{{ count($photos) }} {{ count($photos) === 1 ? 'photo' : 'photos' }}</small></button>
                @foreach($categories as $category)
                    <button type="button" class="filter" data-filter="{{ $category['slug'] }}" aria-pressed="false"><x-site-icon :name="$catIcons[$category['slug']] ?? 'image'" /><span>{{ $category['title'] }}</span><small>{{ $category['count'] }} {{ $category['count'] === 1 ? 'photo' : 'photos' }}</small></button>
                @endforeach
            </div>

            <h2 class="section-title" style="margin-top:2.6rem">Photo Highlights</h2>
            <div class="mosaic">
                @foreach($photos as $photo)
                    <button type="button" class="tile" data-tile data-lightbox-item data-album="{{ $photo['album'] }}" data-full="{{ $photo['url'] }}" data-alt="{{ $photo['alt'] }}" data-caption="{{ $photo['caption'] }}">
                        <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}" decoding="async">
                        <span class="tile__badge"><x-site-icon name="image" /></span>
                        <span class="tile__caption"><strong>{{ $photo['caption'] }}</strong><span>{{ $photo['albumTitle'] }}</span></span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <dialog id="lightbox" class="lightbox" aria-label="Photo viewer">
        <button type="button" class="lightbox__btn lightbox__close" data-lightbox-close aria-label="Close"><x-site-icon name="close" /></button>
        <button type="button" class="lightbox__btn lightbox__prev" data-lightbox-prev aria-label="Previous photo"><x-site-icon name="chevronLeft" /></button>
        <button type="button" class="lightbox__btn lightbox__next" data-lightbox-next aria-label="Next photo"><x-site-icon name="chevronRight" /></button>
        <figure class="lightbox__figure"><img data-lightbox-image alt=""><figcaption data-lightbox-caption></figcaption></figure>
    </dialog>
</section>

@if(count($strip))
<section class="section--tight">
    <div class="site-container">
        <div class="memories">
            <div class="memories__lead"><span class="band__icon"><x-site-icon name="camera" /></span><div><h2>Memories That Inspire</h2><p>These moments capture the vibrant life and unforgettable experiences at {{ $settings->school_name }}.</p></div></div>
            <div class="memories__strip">@foreach($strip as $photo)<a href="{{ $photo['url'] }}" target="_blank" rel="noopener" style="flex:1 1 0;min-width:0;aspect-ratio:4/3;border-radius:6px;overflow:hidden;display:block"><img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover"></a>@endforeach</div>
        </div>
    </div>
</section>
@endif

<section class="section--tight">
    <div class="site-container">
        <div class="share-box">
            <div><h2>Share Your Moments</h2><p>We love seeing our students shine! Send us your photos and be part of our gallery.</p></div>
            <a class="btn btn--outline" href="{{ route('contact', ['subject' => 'Photos for the school gallery']) }}"><x-site-icon name="upload" /> Send us your photos</a>
        </div>
    </div>
</section>
@endsection
