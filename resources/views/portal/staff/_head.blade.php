<section class="portal-head">
    <div class="site-container portal-head__inner">
        <div>
            <h1>{{ $title }}</h1>
            @if(!empty($subtitle))<p>{{ $subtitle }}</p>@endif
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            @if(auth()->user()->can('manage students') || auth()->user()->can('manage results') || auth()->user()->can('manage website content'))<a class="btn btn--ghost-light" href="{{ url('/admin') }}">Admin console</a>@endif
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn--ghost-light">Sign out</button></form>
        </div>
    </div>
</section>
<nav class="portal-nav" aria-label="Staff portal">
    <div class="site-container">
        <ul>
            <li><a href="{{ route('portal.dashboard') }}">Dashboard</a></li>
            <li><a href="{{ route('staff.results') }}"@if(($current ?? '') === 'results') aria-current="page"@endif>Manage results</a></li>
            <li><a href="{{ route('staff.results.archive') }}"@if(($current ?? '') === 'archive') aria-current="page"@endif>Reports archive</a></li>
        </ul>
    </div>
</nav>
@if($errors->any())
    <div class="site-container" style="margin-top:1rem"><div role="alert" class="alert alert--error">@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
@endif
