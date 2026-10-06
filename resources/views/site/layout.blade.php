@php
    $tokens = is_array($settings->theme_tokens ?? null) ? $settings->theme_tokens : [];
    $hex = fn ($key) => (isset($tokens[$key]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $tokens[$key])) ? $tokens[$key] : null;
    $themeCss = '';
    if ($hex('primary')) {
        $c = $hex('primary');
        $themeCss .= "--navy-900:{$c};--navy-950:color-mix(in srgb,{$c} 62%,#000);--navy-800:color-mix(in srgb,{$c} 82%,#3b6cff);--navy-700:color-mix(in srgb,{$c} 62%,#3b6cff);";
    }
    if ($hex('secondary')) {
        $c = $hex('secondary');
        $themeCss .= "--maroon-700:{$c};--maroon-800:color-mix(in srgb,{$c} 78%,#000);--maroon-600:color-mix(in srgb,{$c} 86%,#fff);";
    }
    if ($hex('accent')) {
        $c = $hex('accent');
        $themeCss .= "--gold-500:{$c};--gold-600:color-mix(in srgb,{$c} 82%,#000);--gold-400:color-mix(in srgb,{$c} 84%,#fff);";
    }
    $logoUrl = $settings->logo_path ? asset('storage/'.$settings->logo_path) : null;
    // Section values are already HTML-escaped by Blade, so the fallbacks are escaped here and everything is printed raw below.
    $pageTitle = trim($__env->yieldContent('title')) ?: e($settings->school_name);
    $pageDescription = trim($__env->yieldContent('description')) ?: e($settings->description);
    $canonicalUrl = trim($__env->yieldContent('canonical')) ?: e($settings->canonical_base_url ? rtrim($settings->canonical_base_url, '/').request()->getPathInfo() : url()->current());
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#041433">
    <title>{!! $pageTitle !!}</title>
    <meta name="description" content="{!! $pageDescription !!}">
    <link rel="canonical" href="{!! $canonicalUrl !!}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->school_name }}">
    <meta property="og:title" content="{!! $pageTitle !!}">
    <meta property="og:description" content="{!! $pageDescription !!}">
    <meta property="og:url" content="{!! $canonicalUrl !!}">
    @if($logoUrl)<meta property="og:image" content="{{ $logoUrl }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@500;600;700&display=swap">
    <link rel="icon" href="{{ $settings->favicon_path ? asset('storage/'.$settings->favicon_path) : asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if($themeCss !== '')<style>:root{ {{ $themeCss }} }</style>@endif
    @stack('head')
</head>
<body class="min-h-screen antialiased">
    <a class="skip-link" href="#main-content">Skip to content</a>
    @include('site.partials.icons')
    @include('site.partials.header')
    @if (session('success'))
        <div role="status" class="flash alert alert--ok">{{ session('success') }}</div>
    @endif
    @if (session('status'))
        <div role="status" class="flash alert alert--ok">{{ session('status') }}</div>
    @endif
    @if (session('application_reference'))
        <div role="status" class="flash alert alert--ok">Your application was received. Reference: <strong>{{ session('application_reference') }}</strong>. Please keep it for follow-up.</div>
    @endif
    <main id="main-content">@yield('content')</main>
    @include('site.partials.footer')
    @if(!empty($whatsappUrl) && ! \Illuminate\Support\Str::startsWith($currentPath ?? '', ['portal', 'login', 'forgot-password', 'reset-password']))
        <a class="fab-whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp"><x-site-icon name="whatsapp" /><span>Chat<br>with us</span></a>
    @endif
    <button type="button" class="fab-top" data-back-to-top aria-label="Back to top"><x-site-icon name="arrowUp" /></button>
</body>
</html>
