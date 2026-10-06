@extends('site.layout')
@section('title', $class->name.' results | '.$settings->short_name)
@section('content')
@include('portal.staff._head', ['title' => $class->name.($arm ? ' ('.$arm->name.')' : '').' students', 'subtitle' => $term->name.', '.$term->session?->name.' academic session', 'current' => 'results'])
<div class="portal-body"><div class="site-container">
    @if(session('success'))<div role="status" class="alert alert--ok" style="margin-bottom:1rem">{{ session('success') }}</div>@endif
    <div class="panel">
        <div class="panel__head">
            <h2>{{ count($students) }} {{ count($students) === 1 ? 'student' : 'students' }}</h2>
            <form method="get" action="{{ route('staff.results.class') }}" class="toolbar" style="align-items:center">
                <input type="hidden" name="class" value="{{ $class->id }}">
                @if($arm)<input type="hidden" name="arm" value="{{ $arm->id }}">@endif
                <input type="hidden" name="term" value="{{ $term->id }}">
                <label class="sr-only" for="student-search">Search students</label>
                <input id="student-search" class="sheet-input" style="width:13rem;text-align:left" type="search" name="q" value="{{ $q }}" placeholder="Search name or ID">
                <button class="btn btn--navy btn--sm" type="submit">Search</button>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Full name</th><th>Student ID</th><th>Gender</th><th>Date of birth</th><th class="num">Age</th><th>Result status</th><th></th></tr></thead>
                <tbody>
                @forelse($students as $student)
                    @php
                        $counts = $progress[$student->id] ?? [];
                        $published = $counts['published'] ?? 0;
                        $pending = ($counts['pending'] ?? 0) + ($counts['draft'] ?? 0);
                    @endphp
                    <tr>
                        <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ trim($student->first_name.' '.$student->other_names) }}</td>
                        <td>{{ $student->student_number }}</td>
                        <td>{{ $student->gender ?: '—' }}</td>
                        <td>{{ $student->date_of_birth?->format('D d M, Y') ?: '—' }}</td>
                        <td class="num">{{ $student->date_of_birth ? $student->date_of_birth->age : '—' }}</td>
                        <td>
                            @if($published > 0)<span class="pill pill--ok">{{ $published }} published</span>@endif
                            @if($pending > 0)<span class="pill pill--warn">{{ $pending }} pending</span>@endif
                            @if($published + $pending === 0)<span class="pill">No scores yet</span>@endif
                        </td>
                        <td class="actions">
                            <a class="link-action link-action--green" href="{{ route('staff.results.student', ['student' => $student->id, 'term' => $term->id]) }}">Enter result</a>
                            <a class="link-action link-action--blue" href="{{ route('reports.show', ['student' => $student->id, 'term' => $term->id]) }}" target="_blank" rel="noopener">Print result</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><p class="empty-state" style="margin:.6rem 0">No active students found in this class.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canPublish)
    <div class="panel">
        <div class="panel__head"><h2>Publish results</h2></div>
        <div class="panel__body" style="display:grid;gap:1.2rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
            <form method="post" action="{{ route('staff.results.publish-class') }}" data-confirm="Publish every pending result for this class and term? Students and linked parents will be able to see them.">
                @csrf
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                @if($arm)<input type="hidden" name="arm_id" value="{{ $arm->id }}">@endif
                <input type="hidden" name="term_id" value="{{ $term->id }}">
                <p class="muted" style="margin-top:0">Makes all pending scores for this class visible to students and parents.</p>
                <button class="btn btn--maroon" type="submit">Publish class results</button>
            </form>
            <form method="post" action="{{ route('staff.results.unpublish-class') }}" data-confirm="Return the published results of this class to pending so they can be corrected?">
                @csrf
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                @if($arm)<input type="hidden" name="arm_id" value="{{ $arm->id }}">@endif
                <input type="hidden" name="term_id" value="{{ $term->id }}">
                <label class="field">Reason for correction<input name="reason" required minlength="5" maxlength="255" placeholder="e.g. Wrong CA score entered for Mathematics"></label>
                <button class="btn btn--outline" style="margin-top:.6rem" type="submit">Unpublish for correction</button>
            </form>
        </div>
    </div>
    @endif
    <p style="margin-top:1rem"><a class="link-more" href="{{ route('staff.results', ['term' => $term->id]) }}">&larr; Back to classes</a></p>
</div></div>
@endsection
