@extends('site.layout')
@section('title', ($seoTitle ?? $page->seo_title ?? $page->title).' | '.$settings->short_name)
@section('description', $seoDescription ?? $page->seo_description ?? $page->excerpt ?? $settings->description)
@section('canonical', $page->canonical_url ?: url()->current())
@section('content')
    <section class="bg-white"><div class="site-container max-w-4xl py-12 sm:py-16">
        @if($page->eyebrow)<p class="text-sm font-semibold uppercase tracking-[.16em] text-[var(--brand-600)]">{{ $page->eyebrow }}</p>@endif
        <h1 class="mt-2 font-display text-4xl font-semibold leading-tight text-[var(--brand-800)] sm:text-5xl">{{ $page->title }}</h1>
        @if($page->excerpt)<p class="mt-5 text-lg leading-relaxed text-[var(--ink-soft)]">{{ $page->excerpt }}</p>@endif
        @if($page->featuredMedia)<img src="{{ $page->featuredMedia->url() }}" alt="{{ $page->featuredMedia->alt_text ?: $page->title }}" class="mt-8 max-h-[480px] w-full rounded-[var(--radius-lg)] object-cover" loading="lazy">@endif
        <div class="prose-school mt-8">
            @if(is_array($page->content_blocks) && count($page->content_blocks))
                @foreach($page->content_blocks as $block)
                    @switch($block['type'] ?? '')
                        @case('heading')<h2 class="text-2xl font-semibold">{{ $block['text'] ?? '' }}</h2>@break
                        @case('paragraph')<p>{{ $block['text'] ?? '' }}</p>@break
                        @case('list')@php($items = is_array($block['items'] ?? null) ? $block['items'] : preg_split('/\R/', trim((string)($block['text'] ?? ''))))<ul>@foreach($items as $item)@if(trim((string)$item) !== '')<li>{{ $item }}</li>@endif @endforeach</ul>@break
                        @case('quote')<blockquote class="border-l-4 border-[var(--accent)] pl-5 italic text-[var(--ink-soft)]">{{ $block['text'] ?? '' }}</blockquote>@break
                        @case('image')@php($blockImage = $contentMedia->get($block['media_id'] ?? null))@if($blockImage)<figure><img src="{{ $blockImage->url() }}" alt="{{ $blockImage->alt_text ?: ($block['alt_text'] ?? '') }}" class="my-8 max-h-[520px] w-full rounded-[var(--radius-lg)] object-cover" loading="lazy">@if($blockImage->caption)<figcaption class="mt-2 text-sm text-[var(--ink-soft)]">{{ $blockImage->caption }}</figcaption>@endif</figure>@endif @break
                        @case('cta')@if(\App\Domain\Website\Support\SafePublicUrl::allows($block['cta_url'] ?? null) && !empty($block['cta_label']))<a href="{{ $block['cta_url'] }}" class="my-5 inline-flex rounded-md bg-[var(--brand-700)] px-5 py-3 text-sm font-semibold text-white">{{ $block['cta_label'] }}</a>@endif @break
                    @endswitch
                @endforeach
            @elseif($page->content)
                @foreach(preg_split('/\R\s*\R/', $page->content) as $paragraph)<p>{{ $paragraph }}</p>@endforeach
            @else
                <p>More information will be provided by the school.</p>
            @endif
        </div>
    </div></section>
@endsection
