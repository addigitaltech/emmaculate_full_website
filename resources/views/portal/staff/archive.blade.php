@extends('site.layout')
@section('title', 'Reports archive | '.$settings->short_name)
@section('content')
@include('portal.staff._head', ['title' => 'Reports archive', 'subtitle' => 'Find any student’s report by name, session and term', 'current' => 'archive'])
<div class="portal-body"><div class="site-container">
    <div class="panel">
        <div class="panel__body">
            <form method="get" action="{{ route('staff.results.archive') }}" class="toolbar">
                <label class="field" style="flex:1 1 14rem">Student name or ID<input name="q" value="{{ $q }}" maxlength="80" placeholder="Surname, first name or student ID"></label>
                <label class="field">Session
                    <select name="session"><option value="">All sessions</option>@foreach($sessions as $session)<option value="{{ $session->id }}"@if($sessionId === $session->id) selected @endif>{{ $session->name }}</option>@endforeach</select>
                </label>
                <label class="field">Term
                    <select name="term"><option value="">All terms</option>@foreach($sessions as $session)@foreach($session->terms as $option)<option value="{{ $option->id }}"@if($termId === $option->id) selected @endif>{{ $session->name }} · {{ $option->name }}</option>@endforeach @endforeach</select>
                </label>
                <button class="btn btn--navy" type="submit">Load archive</button>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Student</th><th>Class</th><th>Reports</th></tr></thead>
                <tbody>
                @forelse($students as $student)
                    <tr>
                        <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ trim($student->first_name.' '.$student->other_names) }}<div class="muted" style="font-size:.75rem">{{ $student->student_number }}</div></td>
                        <td>{{ $student->schoolClass?->name ?: '—' }}@if($student->arm) ({{ $student->arm->name }})@endif</td>
                        <td>@foreach(($termsByStudent[$student->id] ?? []) as $reportTerm)<a class="link-action link-action--blue" style="margin:0 .9rem .3rem 0" target="_blank" rel="noopener" href="{{ route('reports.show', ['student' => $student->id, 'term' => $reportTerm->id]) }}">{{ $reportTerm->session?->name }} · {{ $reportTerm->name }}</a>@endforeach</td>
                    </tr>
                @empty
                    <tr><td colspan="3"><p class="empty-state" style="margin:.6rem 0">No reports match your search.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination-wrap">{{ $students->links() }}</div>
</div></div>
@endsection
