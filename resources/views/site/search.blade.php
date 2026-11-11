@extends('site.layout')

@section('title', 'Search | '.$settings->short_name)
@section('description', 'Search the published pages, news, events and information on '.$settings->school_name.'.')

@section('content')
    <x-page-hero eyebrow="Find information" title="Search the website" lead="Find published school information, news, events and answers." :image="$heroImage" :crumbs="[['Home', route('home')], ['Search', null]]" />

    <section class="section">
        <div class="site-container">
            <form class="search-form" method="get" action="{{ route('search') }}" role="search" data-live-search="public">
                <label class="sr-only" for="site-search">Search the website</label>
                <input id="site-search" class="search-form__input" type="search" name="q" value="{{ $query }}" placeholder="Search the school website" minlength="2" maxlength="100" autocomplete="off" autofocus>
                <button class="btn btn--maroon" type="submit"><x-site-icon name="search" /> Search</button>
                <div class="live-search-suggestions" data-live-search-results role="listbox" aria-label="Search suggestions" hidden></div>
            </form>

            @if($query !== '')
                <p class="section-lead">{{ $results->count() }} {{ $results->count() === 1 ? 'result' : 'results' }} for <strong>{{ $query }}</strong></p>
            @endif

            @if($results->isNotEmpty())
                <div class="search-results" aria-live="polite">
                    @foreach($results as $result)
                        <a class="search-result" href="{{ $result['url'] }}">
                            <p class="search-result__type">{{ $result['type'] }}</p>
                            <h2 class="search-result__title">{{ $result['title'] }}</h2>
                            @if($result['excerpt'])<p class="search-result__excerpt">{{ $result['excerpt'] }}</p>@endif
                            @if($result['date'])<time class="search-result__date" datetime="{{ $result['date']->toDateString() }}">{{ $result['date']->format('j F Y') }}</time>@endif
                        </a>
                    @endforeach
                </div>
            @elseif($query !== '')
                <p class="search-empty" role="status">No published information matched your search. Try a different word or phrase.</p>
            @endif
        </div>
    </section>
@endsection
