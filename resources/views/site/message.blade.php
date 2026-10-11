@extends('site.layout')
@section('title', $heading.' | '.$settings->short_name)
@section('content')
<section class="section">
    <div class="site-container" style="max-width:36rem">
        <div class="card card--pad" style="text-align:center">
            <h1 class="h2" style="font-size:1.6rem">{{ $heading }}</h1>
            <p class="muted">{{ $message }}</p>
            <p><a class="btn btn--maroon" href="{{ route('home') }}">Back to the home page</a></p>
        </div>
    </div>
</section>
@endsection
