@extends('site.layout')

@section('title', 'Portal search | '.$settings->short_name)

@section('content')
<section class="portal-head">
    <div class="site-container portal-head__inner">
        <div>
            <h1>Search your portal</h1>
            <p>Search the records and information available to your account.</p>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            <a class="btn btn--ghost-light" href="{{ route('portal.dashboard') }}">Dashboard</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn--ghost-light">Sign out</button></form>
        </div>
    </div>
</section>
<section class="portal-body">
    <div class="site-container">
        <form method="get" action="{{ route('portal.search') }}" class="portal-search-form" role="search" data-live-search="portal">
            <label class="sr-only" for="portal-search">Search your portal</label>
            <input id="portal-search" name="q" type="search" value="{{ $query }}" maxlength="100" minlength="2" placeholder="Search by name, Student ID, subject, class, fee or result" autofocus>
            <button class="btn btn--maroon" type="submit">Search</button>
            <div class="live-search-suggestions" data-live-search-results role="listbox" aria-label="Portal search suggestions" hidden></div>
        </form>

        @if($query !== '')
            <p class="muted" style="margin:1.25rem 0 .8rem">{{ $results->count() }} {{ $results->count() === 1 ? 'match' : 'matches' }} for <strong>{{ $query }}</strong></p>
        @endif

        @if($results->isNotEmpty())
            <div class="portal-search-results" aria-live="polite">
                @foreach($results as $result)
                    <a class="panel portal-search-result" href="{{ $result['url'] }}">
                        <span class="portal-search-result__type">{{ $result['type'] }}</span>
                        <strong>{{ $result['title'] }}</strong>
                        @if($result['excerpt'])<span class="muted">{{ $result['excerpt'] }}</span>@endif
                    </a>
                @endforeach
            </div>
        @elseif($query !== '')
            <p class="empty-state">No accessible portal records matched that search. Try a name, Student ID, class, subject or fee name.</p>
        @endif
    </div>
</section>
@endsection
