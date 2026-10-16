@extends('site.layout')
@section('title', 'Sign in | '.$settings->short_name)
@section('content')
<x-page-hero eyebrow="School portals" title="Which portal do you need?" lead="Each portal has its own sign-in page. Choose yours." :image="asset('storage/migrated-images/students-group.jpg')" :crumbs="[['Home', route('home')], ['Sign in', null]]" />
<section class="section">
    <div class="site-container" style="max-width:62rem">
        @if(count($portalLinks))
            <div class="programmes" style="margin-top:0">
                @foreach($portalLinks as $portalLink)
                    <article class="programme" style="flex-direction:column">
                        <div class="programme__body" style="width:100%">
                            <h2 class="h3">{{ $portalLink->title }}</h2>
                            <p>{{ $portalLink->description }}</p>
                            <a class="btn btn--maroon btn--sm" href="{{ $portalLink->url }}">Open <x-site-icon name="arrow" /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="empty-state">No portal is open right now. Please contact the school office.</p>
        @endif
    </div>
</section>
@endsection
