@extends('site.layout')
@section('title', 'Frequently Asked Questions | '.$settings->short_name)
@section('description', 'Frequently asked questions about '.$settings->school_name.'.')
@section('content')
<x-page-hero eyebrow="Help & information" title="Frequently Asked Questions" lead="Answers to common questions from parents, guardians and students." :image="$heroImage" :crumbs="[['Home', route('home')], ['FAQ', null]]" />
<section class="section"><div class="site-container" style="max-width:52rem">
    @if($faqs->isEmpty())
        <p class="empty-state">Frequently asked questions will be published here when confirmed by the school. For now, please <a href="{{ route('contact') }}" style="text-decoration:underline">contact us</a>.</p>
    @else
        <div class="card card--pad faq">
            @foreach($faqs as $faq)
                <details><summary>{{ $faq->question }} <x-site-icon name="chevron" /></summary><div>@foreach(preg_split('/\R\s*\R/', (string) $faq->answer) as $paragraph)@if(trim($paragraph))<p style="margin:.4rem 0">{{ $paragraph }}</p>@endif @endforeach</div></details>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
