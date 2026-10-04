@extends('site.layout')
@section('title', ($seoTitle ?? $post->title).' | '.$settings->short_name)
@section('description', $seoDescription ?? $post->excerpt ?? $settings->description)
@section('content')
<x-page-hero :eyebrow="$post->category ?: 'News'" :title="$post->title" :lead="$post->subtitle" :image="$heroImage" :crumbs="[['Home', route('home')], ['News & Events', route('news.index')], [\Illuminate\Support\Str::limit($post->title, 40), null]]" />
<section class="section"><article class="site-container article">
    <p class="article__meta">@if($post->published_at)<span class="chip">{{ $post->published_at->format('F j, Y') }}</span>@endif @if($post->author)<span>By {{ $post->author->name }}</span>@endif</p>
    @if($post->coverImage)<div class="article__cover"><img src="{{ $post->coverImage->url() }}" alt="{{ $post->coverImage->alt_text ?: $post->title }}" decoding="async"></div>@endif
    <div class="prose-school">@foreach(preg_split('/\R\s*\R/', (string) $post->content) as $paragraph)@if(trim($paragraph))<p>{{ $paragraph }}</p>@endif @endforeach</div>
    <p style="margin-top:2rem"><a class="btn btn--outline" href="{{ route('news.index') }}"><x-site-icon name="chevronLeft" /> All news</a></p>
</article></section>
@if($related->isNotEmpty())
<section class="section--tight"><div class="site-container"><h2 class="section-title">More News</h2>
    <div class="programmes">@foreach($related as $item)<article class="programme"><div class="programme__body"><span class="muted" style="font-size:.8rem">{{ $item->published_at?->format('M j, Y') }}</span><h3 class="h3">{{ $item->title }}</h3><a class="link-more" href="{{ route('news.show', $item->slug) }}">Read more <x-site-icon name="arrow" /></a></div></article>@endforeach</div>
</div></section>
@endif
@endsection
