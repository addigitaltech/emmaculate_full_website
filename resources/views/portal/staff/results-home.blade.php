@extends('site.layout')
@section('title', 'Manage results | '.$settings->short_name)
@section('content')
@include('portal.staff._head', ['title' => 'Manage results', 'subtitle' => $term ? $term->name.', '.$term->session?->name.' academic session' : 'No academic term has been created yet', 'current' => 'results'])
<div class="portal-body"><div class="site-container">
    <div class="panel">
        <div class="panel__head"><h2>Academic term</h2></div>
        <div class="panel__body">
            <form method="get" action="{{ route('staff.results') }}" class="toolbar">
                <label class="field">Session and term
                    <select name="term" data-autosubmit>
                        @foreach($sessions as $session)
                            @foreach($session->terms as $option)
                                <option value="{{ $option->id }}"@if($term && $term->id === $option->id) selected @endif>{{ $session->name }} · {{ $option->name }}@if($option->is_current) (current)@endif</option>
                            @endforeach
                        @endforeach
                    </select>
                </label>
                <noscript><button class="btn btn--navy btn--sm" type="submit">Show</button></noscript>
            </form>
            @if($sessions->isEmpty())<p class="muted" style="margin:.8rem 0 0">Create the academic session and its terms in the admin console first (Results setup &gt; Academic sessions and terms).</p>@endif
        </div>
    </div>

    @if($term)
    @if(! $isAdmin && $assignments->isNotEmpty())
        <div class="panel">
            <div class="panel__head"><h2>My score sheets</h2><span class="muted" style="font-size:.85rem">Enter CA and exam scores for a whole class</span></div>
            <div class="panel__body">
                <div class="class-grid">
                    @foreach($assignments as $assignment)
                        <a class="panel class-card" style="text-decoration:none;color:inherit;margin:0" href="{{ route('staff.results.subject', ['assignment' => $assignment->id, 'term' => $term->id]) }}">
                            <h3>{{ $assignment->subject?->name }}</h3>
                            <span class="muted">{{ $assignment->schoolClass?->name }}@if($assignment->arm) &middot; {{ $assignment->arm->name }}@endif</span>
                            <span class="link-action link-action--green" style="margin:.7rem 0 0">Open score sheet &rarr;</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="panel">
        <div class="panel__head"><h2>{{ $isAdmin ? 'Classes' : 'My classes' }}</h2><span class="muted" style="font-size:.85rem">Open a class to see its students, enter full result sheets or print reports</span></div>
        <div class="panel__body">
            @if($classes->isEmpty())
                <p class="empty-state">No classes are available. @if($isAdmin)Add classes and arms in the admin console. @else Ask the school to assign you to a class and subject. @endif</p>
            @else
                <div class="class-grid">
                    @foreach($classes as $class)
                        <div class="panel class-card" style="margin:0">
                            <h3>{{ $class->name }}</h3>
                            <div class="chips">
                                @if($class->arms->isEmpty())
                                    <a class="chip-link" href="{{ route('staff.results.class', ['class' => $class->id, 'term' => $term->id]) }}">Open class</a>
                                @else
                                    @foreach($class->arms as $arm)
                                        <a class="chip-link" href="{{ route('staff.results.class', ['class' => $class->id, 'arm' => $arm->id, 'term' => $term->id]) }}">{{ $class->name }} ({{ $arm->name }})</a>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif
</div></div>
@endsection
