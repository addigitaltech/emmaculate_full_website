@php
    $logoUrl = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    $currentPath = $currentPath ?? trim(request()->path(), '/');
@endphp
<footer class="site-footer">
    <div class="site-container footer-grid" style="--footer-cols: {{ count($footerSections) + 2 }}">
        <div>
            <a class="footer-brand" href="{{ route('home') }}" aria-label="{{ $settings->school_name }} home">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="" width="50" height="56">@endif
                <span>{{ $settings->school_name }}@if($settings->motto)<small>{{ $settings->motto }}</small>@endif</span>
            </a>
            @if($settings->description)<p class="footer-about">{{ \Illuminate\Support\Str::limit($settings->description, 150) }}</p>@endif
            @if(!empty($socialLinks))
                <ul class="social footer-social" aria-label="Social media">
                    @foreach($socialLinks as $social)
                        <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}"><x-site-icon :name="$social['icon']" /></a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        @foreach($footerSections as $section)
            <nav aria-label="{{ $section->title }}">
                <h2>{{ $section->title }}</h2>
                <ul class="footer-links">
                    @foreach(($section->links ?? []) as $link)
                        @if(! empty($link['url']) && \App\Domain\Website\Support\SafePublicUrl::allows($link['url']))
                            <li><a href="{{ $link['url'] }}"@if(\App\Domain\Website\Support\ActiveLink::matches($link['url'], $currentPath)) aria-current="page"@endif>{{ $link['label'] ?? 'Link' }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </nav>
        @endforeach

        <div>
            <h2>Contact us</h2>
            <ul class="footer-links footer-contact">
                @if($settings->address)<li><x-site-icon name="pin" /><span>{{ $settings->address }}</span></li>@endif
                @if($settings->phone_primary)<li><x-site-icon name="phone" /><span><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_primary) }}">{{ $settings->phone_primary }}</a>@if($settings->phone_secondary)<br><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone_secondary) }}">{{ $settings->phone_secondary }}</a>@endif</span></li>@endif
                @if($settings->email)<li><x-site-icon name="mail" /><a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a></li>@endif
                @if($settings->office_hours)<li><x-site-icon name="clock" /><span>{{ $settings->office_hours }}</span></li>@endif
                <li><x-site-icon name="send" /><a href="{{ route('contact') }}">Send us a message</a></li>
            </ul>
        </div>

        <div>
            <h2>Newsletter</h2>
            <p class="footer-about" style="margin-top:0">Subscribe to our newsletter to get the latest news and updates.</p>
            @include('site.partials.newsletter-form', ['inputId' => 'footer-newsletter'])
        </div>
    </div>
    <div class="footer-bottom">
        <div class="site-container footer-bottom__inner">
            <p>&copy; {{ now()->year }} {{ $settings->school_name }}. All Rights Reserved.</p>
            <p>@if($settings->show_admin_link)<a class="footer-admin" href="{{ url('/admin') }}">Admin login</a> &nbsp;&middot;&nbsp; @endif<a class="footer-admin" href="{{ route('credit') }}">Designed by Addigitaltech for Education</a></p>
        </div>
    </div>
</footer>
