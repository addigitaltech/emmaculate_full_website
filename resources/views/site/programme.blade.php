@extends('site.layout')
@section('title', ($seoTitle ?? $programme->title).' | '.$settings->short_name)
@section('description', $seoDescription ?? $programme->intro)
@section('content')
@php
    $image = $programme->image?->url() ?? ($programme->image_path ? asset('storage/'.$programme->image_path) : null);
@endphp
<x-page-hero :eyebrow="ucfirst($programme->level)" :title="$programme->title" :lead="\Illuminate\Support\Str::limit((string) $programme->intro, 140)" :image="$heroImage" :crumbs="[['Home', route('home')], ['Academics', route('academics')], [$programme->title, null]]" />
<section class="section">
    <div class="site-container" style="max-width:56rem">
        @if($image)<div class="photo-frame" style="margin-bottom:1.5rem"><img src="{{ $image }}" alt="{{ $programme->image?->alt_text ?: $programme->title }}" style="max-height:440px" loading="lazy" decoding="async"></div>@endif
        <p style="font-size:1.1rem;line-height:1.8" class="muted">{{ $programme->intro }}</p>
        @if($programme->placeholder_note)<aside class="note-bar" style="margin-top:1.5rem"><x-site-icon name="info" /><span>{{ $programme->placeholder_note }}</span></aside>@endif
        @if($programme->approach)<ul class="tick-list" style="margin-top:1.2rem">@foreach($programme->approach as $item)<li><x-site-icon name="check" /><span>{{ $item }}</span></li>@endforeach</ul>@endif
        <p style="margin-top:2rem"><a class="btn btn--outline" href="{{ route('academics') }}">All academics</a> <a class="btn btn--maroon" href="{{ route('admissions') }}">Admissions</a></p>
    </div>
</section>
@endsection
