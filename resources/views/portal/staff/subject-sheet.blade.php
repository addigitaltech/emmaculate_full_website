@extends('site.layout')
@section('title', ($assignment->subject?->name).' score sheet | '.$settings->short_name)
@section('content')
@include('portal.staff._head', ['title' => $assignment->subject?->name.' · '.$assignment->schoolClass?->name.($assignment->arm ? ' ('.$assignment->arm->name.')' : ''), 'subtitle' => $term->name.', '.$term->session?->name.' academic session', 'current' => 'results'])
@php
    $bandData = collect($bands)->map(fn ($band) => ['min' => $band['min_score'], 'max' => $band['max_score'], 'grade' => $band['grade']])->values();
    $failGrade = collect($bands)->sortBy('min_score')->first()['grade'] ?? 'F';
@endphp
<div class="portal-body"><div class="site-container">
    @if(session('success'))<div role="status" class="alert alert--ok" style="margin-bottom:1rem">{{ session('success') }}</div>@endif
    @if(! $termOpen)<div class="alert alert--error" style="margin-bottom:1rem">This is not the current term, so scores can only be changed by a results administrator.</div>@endif
    <form method="post" action="{{ route('staff.results.subject.save', $assignment) }}" data-sheet data-bands='@json($bandData)' data-fail-grade="{{ $failGrade }}">
        @csrf
        <input type="hidden" name="term_id" value="{{ $term->id }}">
        <div class="panel">
            <div class="panel__head"><h2>Class score sheet</h2><span class="muted" style="font-size:.85rem">Maximum marks: @foreach(['ca1' => 'CA 1', 'ca2' => 'CA 2', 'ca3' => 'CA 3', 'exam' => 'Exam'] as $key => $label)@if($maxima[$key] > 0){{ $label }} {{ $maxima[$key] }}@if(! $loop->last) &middot; @endif @endif @endforeach</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>Student</th><th>Offered</th>@foreach(['ca1' => 'CA 1', 'ca2' => 'CA 2', 'ca3' => 'CA 3', 'exam' => 'Exam'] as $key => $label)@if($maxima[$key] > 0)<th class="num">{{ $label }}<br><span style="font-weight:500">/{{ $maxima[$key] }}</span></th>@endif @endforeach<th class="num">Total</th><th class="num">Grade</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($students as $student)
                        @php
                            $result = $results->get($student->id);
                            $published = $result && $result->status === 'published';
                            $locked = $published || ! $termOpen;
                            $offered = $result ? (bool) $result->is_offered : true;
                        @endphp
                        <tr data-row>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ trim($student->first_name.' '.$student->other_names) }}<div class="muted" style="font-size:.75rem">{{ $student->student_number }}</div></td>
                            <td>@if(! $locked)<input type="hidden" name="rows[{{ $student->id }}][offered]" value="0"><input data-offered type="checkbox" name="rows[{{ $student->id }}][offered]" value="1" @checked($offered) aria-label="{{ $student->first_name }} offers this subject">@else<span class="muted">{{ $offered ? 'Yes' : 'No' }}</span>@endif</td>
                            @foreach(['ca1' => 'ca1_score', 'ca2' => 'ca2_score', 'ca3' => 'ca3_score', 'exam' => 'exam_score'] as $key => $column)
                                @if($maxima[$key] > 0)
                                    <td class="num">@if(! $locked)<input data-score class="sheet-input" type="number" step="0.01" min="0" max="{{ $maxima[$key] }}" name="rows[{{ $student->id }}][{{ $key }}]" value="{{ $result ? rtrim(rtrim(number_format((float) $result->$column, 2, '.', ''), '0'), '.') : '' }}" aria-label="{{ $student->first_name }} {{ strtoupper($key) }}">@else{{ $result ? rtrim(rtrim(number_format((float) $result->$column, 2, '.', ''), '0'), '.') : '—' }}@endif</td>
                                @endif
                            @endforeach
                            <td class="num sheet-total" data-total>{{ $result && $offered ? rtrim(rtrim(number_format((float) $result->total_score, 2, '.', ''), '0'), '.') : '—' }}</td>
                            <td class="num"><span class="grade-badge{{ $result && $result->grade === $failGrade ? ' is-fail' : '' }}" data-grade>{{ $result ? ($offered ? $result->grade : 'N/O') : '—' }}</span></td>
                            <td>@if($result)<span class="pill {{ $published ? 'pill--ok' : 'pill--warn' }}">{{ ucfirst($result->status) }}</span>@else<span class="pill">Not entered</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><p class="empty-state" style="margin:.6rem 0">There are no active students in this class.</p></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="sticky-actions">
            <span class="muted" style="font-size:.88rem">Blank lines are ignored. Saved scores stay <strong>pending</strong> until published.</span>
            <button type="submit" class="btn btn--maroon"@if(! $termOpen || $students->isEmpty()) disabled @endif>Save score sheet</button>
        </div>
    </form>
    <p style="margin-top:1rem"><a class="link-more" href="{{ route('staff.results', ['term' => $term->id]) }}">&larr; Back</a></p>
</div></div>
@endsection
