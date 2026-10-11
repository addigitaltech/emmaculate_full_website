@extends('site.layout')
@section('title', 'Designed by '.$creditName.' | '.$settings->short_name)
@section('description', 'This website was designed and built by '.$creditName.'.')
@section('content')
<section class="section">
    <div class="site-container" style="max-width:34rem">
        <div class="card card--pad" style="text-align:center">
            <h1 class="h2" style="font-size:1.6rem">Designed by {{ $creditName }}</h1>
            <span class="rule" aria-hidden="true" style="margin-inline:auto"></span>
            <p class="muted">Want a website or school system like this? Talk to the team that built it.</p>
            <div class="form-stack" style="margin-top:1.4rem">
                <a class="btn btn--maroon btn--block" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Chat with us on WhatsApp</a>
                <a class="btn btn--navy btn--block" href="{{ $websiteUrl }}" target="_blank" rel="noopener noreferrer">Visit our website</a>
            </div>
        </div>
        <p style="margin-top:1rem;text-align:center"><a class="link-more" href="{{ route('home') }}">&larr; Back to {{ $settings->short_name }}</a></p>
    </div>
</section>
@endsection
