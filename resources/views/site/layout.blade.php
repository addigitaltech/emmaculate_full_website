<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings->school_name)</title>
    <meta name="description" content="@yield('description', $settings->description)">
    <link rel="canonical" href="@yield('canonical', $settings->canonical_base_url ? rtrim($settings->canonical_base_url, '/').request()->getPathInfo() : url()->current())">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', $settings->school_name)">
    <meta property="og:description" content="@yield('description', $settings->description)">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" href="{{ $settings->favicon_path ? asset('storage/'.$settings->favicon_path) : asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen antialiased">
    <a class="skip-link" href="#main-content">Skip to content</a>
    @include('site.partials.header')
    @if (session('success'))
        <div role="status" class="mx-auto mt-4 max-w-5xl rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('success') }}</div>
    @endif
    @if (session('application_reference'))
        <div role="status" class="mx-auto mt-4 max-w-5xl rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">Your enquiry was received. Reference: <strong>{{ session('application_reference') }}</strong></div>
    @endif
    <main id="main-content">@yield('content')</main>
    @include('site.partials.footer')
</body>
</html>
