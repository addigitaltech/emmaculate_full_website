@extends('site.layout')
@section('title', ($seoTitle ?? $page->seo_title ?? $page->title).' | '.$settings->short_name)
@section('description', $seoDescription ?? $page->seo_description ?? $page->excerpt ?? $settings->description)
@section('canonical', $page->canonical_url ?: url()->current())
@section('content')
<x-page-hero :eyebrow="$page->eyebrow" :title="$page->title" :lead="$page->excerpt" :image="$heroImage" :crumbs="[['Home', route('home')], [$page->title, null]]" />
<section class="section"><div class="site-container" style="max-width:52rem"><div class="prose-school">
    @if(is_array($page->content_blocks) && count($page->content_blocks))
        @foreach($page->content_blocks as $block)
            @switch($block['type'] ?? '')
                @case('section')<h2>{{ $block['text'] ?? '' }}</h2>@break
                @case('heading')<h3>{{ $block['text'] ?? '' }}</h3>@break
                @case('paragraph')<p>{{ $block['text'] ?? '' }}</p>@break
                @case('list')
                    @php
                        $items = \App\Domain\Website\Support\PageBlocks::lines($block);
                    @endphp
                    <ul>@foreach($items as $item)<li>{{ $item }}</li>@endforeach</ul>
                    @break
                @case('quote')<blockquote style="border-left:4px solid var(--gold-400);padding-left:1.2rem;font-style:italic">{{ $block['text'] ?? '' }}</blockquote>@break
                @case('image')
                    @php
                        $blockImage = $contentMedia->get($block['media_id'] ?? null);
                    @endphp
                    @if($blockImage)<figure><img src="{{ $blockImage->url() }}" alt="{{ $blockImage->alt_text ?: ($block['alt_text'] ?? '') }}" style="margin:2rem 0;max-height:520px;width:100%;object-fit:cover;border-radius:var(--radius)" loading="lazy">@if($blockImage->caption)<figcaption class="muted" style="font-size:.9rem">{{ $blockImage->caption }}</figcaption>@endif</figure>@endif
                    @break
                @case('cta')@if(\App\Domain\Website\Support\SafePublicUrl::allows($block['cta_url'] ?? null) && !empty($block['cta_label']))<a href="{{ $block['cta_url'] }}" class="btn btn--maroon" style="margin:1rem 0">{{ $block['cta_label'] }}</a>@endif @break
            @endswitch
        @endforeach
    @elseif($page->content)
        @foreach(preg_split('/\R\s*\R/', $page->content) as $paragraph)<p>{{ $paragraph }}</p>@endforeach
    @else
        <p>More information will be provided by the school.</p>
    @endif
</div></div></section>
@endsection
