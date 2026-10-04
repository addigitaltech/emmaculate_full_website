@props(['title', 'eyebrow' => null, 'lead' => null, 'image' => null, 'crumbs' => []])
<section class="page-hero">
    @if($image)<div class="page-hero__media"><img src="{{ $image }}" alt="" fetchpriority="high" decoding="async"></div>@endif
    <div class="site-container page-hero__inner">
        <div>
            @if($eyebrow)<p class="page-hero__eyebrow">{{ $eyebrow }}</p>@endif
            <h1 class="page-hero__title">{{ $title }}</h1>
            <span class="page-hero__rule" aria-hidden="true"></span>
            @if($lead)<p class="page-hero__lead">{{ $lead }}</p>@endif
            @if(trim((string) $slot) !== '')<div class="page-hero__actions">{{ $slot }}</div>@endif
        </div>
        @if(count($crumbs))
            <nav aria-label="Breadcrumb">
                <ol class="crumbs">
                    @foreach($crumbs as $index => $crumb)
                        <li>@if($crumb[1] && ! $loop->last)<a href="{{ $crumb[1] }}">{{ $crumb[0] }}</a>@else<span @if($loop->last) aria-current="page" @endif>{{ $crumb[0] }}</span>@endif</li>
                        @if(! $loop->last)<li aria-hidden="true"><x-site-icon name="chevronRight" /></li>@endif
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
</section>
