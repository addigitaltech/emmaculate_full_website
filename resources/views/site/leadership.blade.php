@extends('site.layout')
@section('title', 'Leadership | '.$settings->short_name)
@section('description', 'Meet the leadership team of '.$settings->school_name.'.')
@section('content')
<x-page-hero title="School Leadership" lead="The people serving the Emmaculate Academy community." :image="$heroImage" :crumbs="[['Home', route('home')], ['About Us', route('about')], ['Leadership', null]]" />
<section class="section"><div class="site-container">
    @if($leaders->isEmpty())
        <p class="empty-state">Leadership profiles will be added here.</p>
    @else
        <div class="leader-grid" style="margin-top:0">
            @foreach($leaders as $leader)
                @php
                    $photo = $leader->photo?->url() ?? ($leader->photo_path ? asset('storage/'.$leader->photo_path) : null);
                @endphp
                <article class="leader-card">
                    <div class="leader-card__photo">@if($photo)<img src="{{ $photo }}" alt="{{ $leader->name }}, {{ $leader->title }}" loading="lazy" decoding="async">@else<span>Photo coming soon</span>@endif</div>
                    <div class="leader-card__body">
                        <p class="leader__role" style="font-size:1rem">{{ $leader->title }}</p>
                        <h2 class="leader__name" style="font-size:1.1rem;margin-top:.4rem">{{ $leader->name }}</h2>
                        @if($leader->biography)<p class="leader__bio">{{ $leader->biography }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
