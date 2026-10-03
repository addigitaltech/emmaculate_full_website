@php($logoUrl = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null)
<div class="border-b border-[var(--border)] bg-white text-xs text-[var(--ink-soft)]">
    <div class="site-container flex min-h-9 flex-wrap items-center justify-between gap-x-6 gap-y-1 py-2">
        <span>{{ $settings->address ?: 'Emmaculate Academy' }}</span>
        <div class="flex flex-wrap items-center gap-4">
            @if($settings->phone_primary)<a href="tel:{{ $settings->phone_primary }}">{{ $settings->phone_primary }}</a>@endif
            @if($settings->email)<a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a>@endif
            @if($settings->office_hours)<span>{{ $settings->office_hours }}</span>@endif
        </div>
    </div>
</div>
<header class="sticky top-0 z-40 border-b border-[var(--border)] bg-white/95 backdrop-blur" x-data="{ mobileOpen: false }">
    <div class="site-container flex min-h-[76px] items-center justify-between gap-4 py-3">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 no-underline" aria-label="{{ $settings->school_name }} home">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $settings->school_name }} crest" width="48" height="54" class="h-12 w-auto object-contain">@endif
            <span class="min-w-0 leading-tight">
                <span class="block truncate font-display text-lg font-semibold text-[var(--brand-800)]">{{ $settings->school_name }}</span>
                @if($settings->motto)<span class="block text-xs text-[var(--ink-soft)]">{{ $settings->motto }}</span>@endif
            </span>
        </a>
        <nav aria-label="Primary" class="hidden lg:block">
            <ul class="flex items-center gap-1">
                @foreach($navigation as $item)
                    <li class="relative" x-data="{ open: false }">
                        <div class="flex items-center">
                            <a href="{{ $item->url }}" class="rounded px-3 py-2 text-sm font-medium text-[var(--ink)] hover:text-[var(--brand-700)]">{{ $item->label }}</a>
                            @if($item->children->isNotEmpty())
                                <button type="button" class="flex h-9 w-8 items-center justify-center rounded text-xs hover:text-[var(--brand-700)]" aria-label="Toggle {{ $item->label }} submenu" :aria-expanded="open.toString()" @click="open = !open" @keydown.escape.window="open = false"><span aria-hidden="true" x-text="open ? '▴' : '▾'"></span></button>
                            @endif
                        </div>
                        @if($item->children->isNotEmpty())
                            <div x-cloak x-show="open" x-transition @click.outside="open = false" class="absolute left-0 top-full z-50 mt-1 w-64 rounded-lg border border-[var(--border)] bg-white p-2 shadow-xl">
                                @foreach($item->children as $child)<a href="{{ $child->url }}" class="block rounded px-3 py-2 text-sm text-[var(--ink)] hover:bg-[var(--surface-alt)]">{{ $child->label }}</a>@endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
        <div class="hidden shrink-0 items-center gap-3 lg:flex">
            <a href="{{ route('admissions') }}" class="rounded-md bg-[var(--brand-700)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-800)]">Admissions</a>
            <a href="{{ route('portal') }}" class="rounded-md border border-[var(--border)] px-4 py-2.5 text-sm font-semibold text-[var(--brand-800)] hover:bg-[var(--surface-alt)]">Portal</a>
        </div>
        <button type="button" class="flex h-11 w-11 items-center justify-center rounded border border-[var(--border)] bg-white lg:hidden" aria-label="Toggle menu" aria-controls="mobile-nav" :aria-expanded="mobileOpen.toString()" @click="mobileOpen = !mobileOpen">
            <svg x-show="!mobileOpen" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-cloak x-show="mobileOpen" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <nav id="mobile-nav" aria-label="Mobile" x-cloak x-show="mobileOpen" x-transition class="border-t border-[var(--border)] bg-white lg:hidden">
        <ul class="max-h-[70vh] overflow-y-auto px-4 py-2">
            @foreach($navigation as $item)
                <li class="border-b border-[var(--border)] last:border-0" x-data="{ expanded: false }">
                    <div class="flex items-center justify-between">
                        <a href="{{ $item->url }}" class="flex min-h-11 flex-1 items-center py-2 font-medium">{{ $item->label }}</a>
                        @if($item->children->isNotEmpty())<button type="button" class="flex h-11 w-11 items-center justify-center" aria-label="Toggle {{ $item->label }} submenu" :aria-expanded="expanded.toString()" @click="expanded = !expanded"><span aria-hidden="true" x-text="expanded ? '−' : '+'"></span></button>@endif
                    </div>
                    @if($item->children->isNotEmpty())
                        <ul x-cloak x-show="expanded" class="pb-2 pl-4 text-sm text-[var(--ink-soft)]">
                            @foreach($item->children as $child)<li><a href="{{ $child->url }}" class="flex min-h-11 items-center py-2">{{ $child->label }}</a></li>@endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="flex gap-3 px-4 py-4">
            <a href="{{ route('admissions') }}" class="flex min-h-11 flex-1 items-center justify-center rounded-md bg-[var(--brand-700)] px-4 text-sm font-semibold text-white">Admissions</a>
            <a href="{{ route('portal') }}" class="flex min-h-11 flex-1 items-center justify-center rounded-md border border-[var(--border)] px-4 text-sm font-semibold text-[var(--brand-800)]">Portal</a>
        </div>
    </nav>
</header>
