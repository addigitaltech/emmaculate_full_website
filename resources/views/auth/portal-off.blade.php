@extends('site.layout')
@section('title', 'Portal unavailable | '.$settings->short_name)
@section('content')
<section class="section">
    <div class="site-container" style="max-width:36rem">
        <div class="card card--pad" style="text-align:center">
            <h1 class="h2" style="font-size:1.6rem">The {{ $portal }} portal is switched off</h1>
            <p class="muted">The school has temporarily closed this portal.@if(\App\Domain\Auth\Support\PortalAccess::checkerEnabled()) You can still check results with your admission number and surname.@else Please contact the school office.@endif</p>
            <p style="display:flex;gap:.7rem;justify-content:center;flex-wrap:wrap">
                @if(\App\Domain\Auth\Support\PortalAccess::checkerEnabled())<a class="btn btn--maroon" href="{{ route('result-check') }}">Check Result</a>@endif
                <a class="btn btn--outline" href="{{ route('home') }}">Back to the home page</a>
            </p>
        </div>
    </div>
</section>
@endsection
