@php
    $logoUrl = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $currentPath = $currentPath ?? trim(request()->path(), '/');
    $hasTopbar = filled($settings->phone_primary) || filled($settings->email) || filled($settings->address) || ! empty($socialLinks);
@endphp
@if($hasTopbar)
<div class="topbar">
    <div class="site-container topbar__inner">
        <ul class="topbar__list">
            @if($settings->phone_primary)<li class="topbar__item"><x-site-icon name="phone" /><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_primary) }}">{{ $settings->phone_primary }}</a></li>@endif
            @if($settings->email)<li class="topbar__item topbar__item--optional"><x-site-icon name="mail" /><a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></li>@endif
            @if($settings->address)<li class="topbar__item topbar__item--optional"><x-site-icon name="pin" /><span>{{ $settings->address }}</span></li>@endif
        </ul>
        @if(!empty($socialLinks))
            <ul class="social" aria-label="Social media">
                @foreach($socialLinks as $social)
                    <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}"><x-site-icon :name="$social['icon']" /></a></li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endif
<header class="site-header">
    <div class="site-container site-header__inner">
        <a href="{{ route('home') }}" class="brand" aria-label="{{ $settings->school_name }} home">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="" width="60" height="66" class="brand__logo">@endif
            <span class="brand__text">
                <span class="brand__name">{{ $settings->school_name }}</span>
                @if($settings->motto)<span class="brand__motto">{{ $settings->motto }}</span>@endif
            </span>
        </a>

        <nav class="main-nav" aria-label="Primary">
            <ul class="main-nav__list">
                @foreach($navigation as $item)
                    @php
                        $active = \App\Domain\Website\Support\ActiveLink::item($item, $currentPath);
                    @endphp
                    <li class="main-nav__item{{ $item->children->isNotEmpty() ? ' has-dropdown' : '' }}{{ $active ? ' is-active' : '' }}">
                        <a class="main-nav__link" href="{{ $item->url }}"@if($active) aria-current="page"@endif>{{ $item->label }}</a>
                        @if($item->children->isNotEmpty())
                            <button type="button" class="main-nav__chev" data-dropdown-toggle aria-expanded="false" aria-label="Show {{ $item->label }} submenu"><x-site-icon name="chevron" /></button>
                            <div class="dropdown">
                                @foreach($item->children as $child)
                                    <a href="{{ $child->url }}"@if(\App\Domain\Website\Support\ActiveLink::matches($child->url, $currentPath)) aria-current="page"@endif>{{ $child->label }}</a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="header-actions">
            @if(!empty($portalLinks) && count($portalLinks))
                <div class="portals-wrap has-dropdown">
                    <button type="button" class="portals-btn" data-dropdown-toggle aria-expanded="false" aria-haspopup="true">Portals <x-site-icon name="chevron" /></button>
                    <div class="dropdown dropdown--right">
                        @foreach($portalLinks as $portalLink)
                            @if($portalLink->isLive())
                                <a href="{{ $portalLink->url }}">{{ $portalLink->title }}</a>
                            @else
                                <span class="dropdown__muted">{{ $portalLink->title }} <span class="dropdown__tag">Coming soon</span></span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <button type="button" class="nav-toggle" data-nav-toggle aria-controls="mobile-nav" aria-expanded="false" aria-label="Open menu">
            <span data-icon-open><x-site-icon name="menu" /></span>
            <span data-icon-close hidden><x-site-icon name="close" /></span>
        </button>
    </div>

    <nav id="mobile-nav" class="mobile-nav" aria-label="Mobile">
        <ul>
            @foreach($navigation as $item)
                <li>
                    <div class="mobile-nav__row">
                        <a href="{{ $item->url }}"@if(\App\Domain\Website\Support\ActiveLink::matches($item->url, $currentPath)) aria-current="page"@endif>{{ $item->label }}</a>
                        @if($item->children->isNotEmpty())
                            <button type="button" class="mobile-nav__toggle" data-sub-toggle aria-controls="sub-{{ $item->id }}" aria-expanded="false" aria-label="Show {{ $item->label }} submenu"><x-site-icon name="chevron" /></button>
                        @endif
                    </div>
                    @if($item->children->isNotEmpty())
                        <div id="sub-{{ $item->id }}" class="mobile-nav__sub">
                            @foreach($item->children as $child)<a href="{{ $child->url }}">{{ $child->label }}</a>@endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mobile-nav__cta">
            <a class="btn btn--maroon" href="{{ route('admissions') }}">Admissions</a>
            <a class="btn btn--navy" href="{{ route('portal') }}">Portals</a>
        </div>
    </nav>
</header>
