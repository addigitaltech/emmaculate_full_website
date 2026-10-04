@extends('site.layout')
@section('title', 'School Portals | '.$settings->short_name)
@section('description', 'Secure portal access for '.$settings->school_name.' students, parents, teachers and administrators.')
@section('content')
<x-page-hero eyebrow="School portals" title="One secure school account" lead="Sign in with an account provided by the school. Students see their own published results, parents see linked children, and teachers see assigned classes and subjects." :image="asset('storage/migrated-images/students-group.jpg')" :crumbs="[['Home', route('home')], ['Portals', null]]" />
<section class="section"><div class="site-container">
    <div class="programmes" style="margin-top:0">
        @forelse($portalLinks as $portalLink)
            <article class="programme" style="flex-direction:column">
                <div class="programme__body" style="width:100%">
                <div style="display:flex;justify-content:space-between;gap:.8rem;width:100%"><h2 class="h3">{{ $portalLink->title }}</h2><span class="chip{{ $portalLink->isLive() ? '' : ' chip--soft' }}" style="align-self:flex-start">{{ $portalLink->isLive() ? 'Live' : 'Coming Soon' }}</span></div>
                    @if($portalLink->description)<p>{{ $portalLink->description }}</p>@endif
                    @if($portalLink->isLive())<a class="btn btn--maroon btn--sm" href="{{ $portalLink->url }}">Open portal <x-site-icon name="arrow" /></a>@else<span class="btn btn--outline btn--sm" aria-disabled="true" style="opacity:.6;cursor:not-allowed">Coming Soon</span>@endif
                </div>
            </article>
        @empty
            <p class="empty-state">Portal access information is being prepared. Please contact the school office.</p>
        @endforelse
    </div>
    <p class="note-bar" style="margin-top:1.6rem"><x-site-icon name="info" /><span>Please use the secure sign-in supplied by the school. Never share your password with anyone.</span></p>
</div></section>
@endsection
