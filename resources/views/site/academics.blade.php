@php
    $why = \App\Domain\Website\Support\SiteSections::items('academics.why');
    $curriculum = \App\Domain\Website\Support\SiteSections::items('academics.curriculum');
    $approach = \App\Domain\Website\Support\SiteSections::items('academics.approach');
    $badgeTones = ['navy', 'maroon', 'gold'];
    $badgeIcons = ['flask', 'bookText', 'monitor'];
    $whyColors = ['navy-800', 'maroon-700', 'gold-500', 'navy-800', 'maroon-700'];
@endphp
@extends('site.layout')
@section('title', 'Academics | '.$settings->short_name)
@section('description', $seoDescription ?? $settings->description)
@section('content')
<x-page-hero title="Academics" lead="We provide a well-rounded education that builds knowledge, character and leadership for a brighter future." :image="$heroImage" :crumbs="[['Home', route('home')], ['Academics', null]]" />

<section class="section">
    <div class="site-container">
            <h2 class="section-title">Our Academic Programs</h2>
            <div class="programmes">
                @forelse($programmes as $programme)
                @php
                    $programmeImage = $programme->image?->url() ?? ($programme->image_path ? asset('storage/'.$programme->image_path) : null);
                @endphp
                <article class="programme programme--tall">
                    @if($programmeImage)<div class="programme__img"><img src="{{ $programmeImage }}" alt="{{ $programme->image?->alt_text ?: $programme->title }}" loading="lazy" decoding="async"></div>@endif
                    <div class="programme__body">
                        <span class="programme__badge tone-{{ $badgeTones[$loop->index % 3] }}"><x-site-icon :name="$badgeIcons[$loop->index % 3]" /></span>
                        <h3 class="h3">{{ $programme->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit((string) $programme->intro, 150) }}</p>
                        <a class="link-more" href="{{ route('academics.programme', $programme->slug) }}">Learn more <x-site-icon name="arrow" /></a>
                    </div>
                </article>
            @empty
                <p class="empty-state">Academic programmes will be published here soon.</p>
            @endforelse
        </div>
    </div>
</section>

@if(count($why))
<section class="section--tight">
    <div class="site-container">
        <h2 class="section-title">{{ \App\Domain\Website\Support\SiteSections::title('academics.why', 'Why Our Academics Stand Out') }}</h2>
        <div class="features features--ruled">
            @foreach($why as $item)
                <div class="feature">
                    <span class="feature__icon" style="background:none;color:var({{ '--'.$whyColors[$loop->index % 5] }})"><x-site-icon :name="$item['icon'] ?? 'star'" style="width:2.2rem;height:2.2rem" /></span>
                    <h3 class="feature__title">{{ $item['title'] ?? '' }}</h3>
                    <p class="feature__text">{{ $item['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(count($curriculum) || count($approach))
<section class="section">
    <div class="site-container grid-2" style="align-items:stretch">
        @if(count($curriculum))
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.35rem">{{ \App\Domain\Website\Support\SiteSections::title('academics.curriculum', 'Curriculum Overview') }}</h2><span class="rule" aria-hidden="true"></span>
            @if(\App\Domain\Website\Support\SiteSections::description('academics.curriculum'))<p class="muted">{{ \App\Domain\Website\Support\SiteSections::description('academics.curriculum') }}</p>@endif
            <ul class="tick-list" style="border:0">
                @foreach($curriculum as $item)<li><x-site-icon name="checkCircle" /><span><strong>{{ $item['title'] ?? '' }}</strong>@if(!empty($item['text']))<br><span class="muted" style="font-size:.84rem">{{ $item['text'] }}</span>@endif</span></li>@endforeach
            </ul>
        </div>
        @endif
        @if(count($approach))
        <div class="card card--pad">
            <h2 class="h3" style="font-size:1.35rem">{{ \App\Domain\Website\Support\SiteSections::title('academics.approach', 'Our Learning Approach') }}</h2><span class="rule" aria-hidden="true"></span>
            @if(\App\Domain\Website\Support\SiteSections::description('academics.approach'))<p class="muted">{{ \App\Domain\Website\Support\SiteSections::description('academics.approach') }}</p>@endif
            <ul class="tick-list">
                @foreach($approach as $item)<li><x-site-icon name="check" /><span>{{ $item['title'] ?? '' }}</span></li>@endforeach
            </ul>
        </div>
        @endif
    </div>
</section>
@endif

<section class="section--tight">
    <div class="site-container">
        <div class="band band--navy">
            <div class="band__lead"><span class="band__icon"><x-site-icon name="cap" /></span><div><h2 class="band__title">{{ \App\Domain\Website\Support\SiteSections::title('cta.academics', 'Empowering Minds. Shaping Leaders.') }}</h2><p class="band__text">{{ \App\Domain\Website\Support\SiteSections::description('cta.academics', 'We are committed to raising morally upright leaders through quality education and strong values.') }}</p></div></div>
            <a class="btn btn--gold" href="{{ route('admissions') }}">Admissions <x-site-icon name="arrow" /></a>
        </div>
    </div>
</section>
@endsection
