@php
    $mission = $groups['mission']['paragraphs'][0] ?? null;
    $vision = $groups['vision']['paragraphs'][0] ?? null;
    $pledge = $groups['pledge']['items'] ?? [];
    $anthem = $groups['anthem']['items'] ?? [];
@endphp
@extends('site.layout')
@section('title', ($seoTitle ?? 'Mission, Vision, Pledge & Anthem').' | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@section('content')
<x-page-hero eyebrow="Our guiding purpose" :title="$page?->title ?: 'Our Mission, Vision, Pledge & Anthem'" :lead="'These are the values and beliefs that guide everything we do at '.$settings->school_name.'.'" :image="$heroImage" :crumbs="[['Home', route('home')], ['Mission & Vision', null]]" />

<section class="section">
    <div class="site-container">
        @if($mission || $vision)
        <div class="mv mv--big">
            @if($mission)<article class="mv__card mv__card--maroon"><span class="mv__icon tone-maroon"><x-site-icon name="target" /></span><div><h2 class="mv__title tone-text-maroon">Our Mission</h2><span class="rule" aria-hidden="true" style="margin:.6rem 0 1rem"></span><p class="mv__text" style="margin:0">{{ $mission }}</p></div></article>@endif
            @if($vision)<article class="mv__card mv__card--navy"><span class="mv__icon tone-navy"><x-site-icon name="eye" /></span><div><h2 class="mv__title tone-text-navy">Our Vision</h2><span class="rule" aria-hidden="true" style="margin:.6rem 0 1rem"></span><p class="mv__text" style="margin:0">{{ $vision }}</p></div></article>@endif
        </div>
        @endif

        @if(count($pledge) || count($anthem))
        <div class="duo" style="margin-top:1.4rem">
            @if(count($pledge))
            <article class="duo__card">
                <div class="duo__body">
                    <div class="duo__head"><span class="mv__icon tone-maroon"><x-site-icon name="handshake" /></span><h2 class="tone-text-maroon">School Pledge</h2></div>
                    <ul class="tick-list">
                        @foreach($pledge as $line)<li><span class="tick-list__dot"><x-site-icon name="check" /></span><span>{{ $line }}</span></li>@endforeach
                    </ul>
                </div>
                <div class="duo__photo"><img src="{{ asset('storage/migrated-images/textbooks.jpg') }}" alt="Students of {{ $settings->school_name }}" loading="lazy" decoding="async"></div>
            </article>
            @endif
            @if(count($anthem))
            <article class="duo__card duo__card--navy">
                <div class="duo__body">
                    <div class="duo__head"><span class="mv__icon tone-navy"><x-site-icon name="music" /></span><h2 class="tone-text-navy">School Anthem</h2></div>
                    <ul class="dot-list">
                        @foreach($anthem as $line)<li>{{ $line }}</li>@endforeach
                    </ul>
                </div>
                <div class="duo__photo"><img src="{{ asset('storage/migrated-images/students-reading.jpg') }}" alt="Students of {{ $settings->school_name }} reading together" loading="lazy" decoding="async"></div>
            </article>
            @endif
        </div>
        @endif

        <div class="pull-quote" style="margin-top:1.4rem">
            <x-site-icon name="quote" class="pull-quote__mark" />
            <p class="pull-quote__text">{{ \App\Domain\Website\Support\SiteSections::description('mission.closing', 'Our mission gives us direction, our vision gives us purpose, our pledge keeps us grounded, and our anthem unites us as one family.') }}</p>
            @if($settings->motto)<div class="pull-quote__brand"><x-site-icon name="award" /><span>{{ $settings->motto }}</span></div>@endif
        </div>
    </div>
</section>
@endsection
