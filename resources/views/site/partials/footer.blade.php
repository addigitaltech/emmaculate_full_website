<footer class="mt-16 bg-[var(--surface-dark)] text-white/85">
    <div class="site-container grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <div class="flex items-center gap-3">
                @if($settings->logo_path)<img src="{{ asset('storage/'.$settings->logo_path) }}" alt="{{ $settings->school_name }} crest" width="44" height="50" class="h-11 w-auto object-contain">@endif
                <span class="font-display text-lg font-semibold text-white">{{ $settings->school_name }}</span>
            </div>
            @if($settings->motto)<p class="mt-4 max-w-sm text-sm text-white/70">{{ $settings->motto }}</p>@endif
            @if($settings->address)<p class="mt-2 text-sm text-white/70">{{ $settings->address }}</p>@endif
            @if(!empty($settings->social_links))
                <ul class="mt-5 flex flex-wrap gap-3">
                    @foreach($settings->social_links as $network => $url)
                        @if(\App\Domain\Website\Support\SafePublicUrl::allows($url) && $url)<li><a href="{{ $url }}" class="text-sm underline decoration-white/40 underline-offset-4 hover:decoration-white" target="_blank" rel="noopener noreferrer">{{ ucfirst($network) }}</a></li>@endif
                    @endforeach
                </ul>
            @endif
        </div>
        @foreach($footerSections as $section)
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ $section->title }}</h2>
                <ul class="mt-4 space-y-2">
                    @foreach(($section->links ?? []) as $link)
                        @if(\App\Domain\Website\Support\SafePublicUrl::allows($link['url'] ?? null))<li><a href="{{ $link['url'] ?? '#' }}" class="text-sm text-white/70 hover:text-white">{{ $link['label'] ?? 'Link' }}</a></li>@endif
                    @endforeach
                </ul>
            </div>
        @endforeach
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Contact</h2>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                @if($settings->phone_primary)<li><a href="tel:{{ $settings->phone_primary }}">{{ $settings->phone_primary }}</a></li>@endif
                @if($settings->email)<li><a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></li>@endif
                @if($settings->office_hours)<li>{{ $settings->office_hours }}</li>@endif
                <li><a href="{{ route('contact') }}">Send a message</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/15">
        <div class="site-container flex flex-col gap-3 py-5 text-sm text-white/60 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $settings->school_name }}. All rights reserved.</p>
            <p>Arigidi Akoko, Ondo State</p>
        </div>
    </div>
</footer>
