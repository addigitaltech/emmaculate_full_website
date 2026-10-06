@extends('site.layout')
@section('title', 'Result sheet · '.$student->fullName().' | '.$settings->short_name)
@section('content')
@include('portal.staff._head', ['title' => 'Result sheet', 'subtitle' => $term->name.', '.$term->session?->name.' academic session', 'current' => 'results'])
@php
    $bandData = collect($bands)->map(fn ($band) => ['min' => $band['min_score'], 'max' => $band['max_score'], 'grade' => $band['grade']])->values();
    $failGrade = collect($bands)->sortBy('min_score')->first()['grade'] ?? 'F';
    $labels = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];
@endphp
<div class="portal-body"><div class="site-container">
    @if(session('success'))<div role="status" class="alert alert--ok" style="margin-bottom:1rem">{{ session('success') }}</div>@endif
    <div class="panel"><div class="panel__body" style="display:flex;flex-wrap:wrap;gap:1rem 2rem;align-items:center;justify-content:space-between">
        <div class="student-chip">
            @if($student->photo)<img src="{{ $student->photo->url() }}" alt="" width="64" height="64">@else<span class="student-chip__ph">{{ strtoupper(substr($student->first_name, 0, 1)) }}</span>@endif
            <div><strong style="font-size:1.15rem">{{ strtoupper($student->last_name) }}, {{ trim($student->first_name.' '.$student->other_names) }}</strong>
            <div class="muted" style="font-size:.9rem">{{ $student->student_number }} &middot; {{ $student->schoolClass?->name }}@if($student->arm) ({{ $student->arm->name }})@endif &middot; {{ $student->gender ?: '—' }}</div></div>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            <a class="btn btn--outline btn--sm" href="{{ route('staff.results.class', ['class' => $student->school_class_id, 'arm' => $student->arm_id, 'term' => $term->id]) }}">&larr; Class list</a>
            <a class="btn btn--navy btn--sm" target="_blank" rel="noopener" href="{{ route('reports.show', ['student' => $student->id, 'term' => $term->id]) }}">Print result</a>
        </div>
    </div></div>

    @if(! $termOpen)<div class="alert alert--error" style="margin-top:1rem">This is not the current term, so scores can only be changed by a results administrator.</div>@endif

    <form method="post" action="{{ route('staff.results.student.save', $student) }}" data-sheet data-bands='@json($bandData)' data-fail-grade="{{ $failGrade }}">
        @csrf
        <input type="hidden" name="term_id" value="{{ $term->id }}">
        <div class="panel" style="margin-top:1.2rem">
            <div class="panel__head"><h2>Subjects and scores</h2><span class="muted" style="font-size:.85rem">Untick a subject the student does not offer</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Offered</th><th>Subject</th><th class="num">CA 1</th><th class="num">CA 2</th><th class="num">CA 3</th><th class="num">Exam</th><th class="num">Total</th><th class="num">Grade</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php
                            $result = $row['result'];
                            $maxima = $row['maxima'];
                            $locked = ! $row['editable'] || ! $termOpen;
                            $offered = $result ? (bool) $result->is_offered : true;
                        @endphp
                        <tr data-row>
                            <td>
                                @if(! $locked)<input type="hidden" name="rows[{{ $row['subject']->id }}][offered]" value="0"><input data-offered type="checkbox" name="rows[{{ $row['subject']->id }}][offered]" value="1" @checked($offered) aria-label="{{ $row['subject']->name }} offered">@else<span class="muted">{{ $offered ? 'Yes' : 'No' }}</span>@endif
                            </td>
                            <td><strong>{{ $row['subject']->name }}</strong></td>
                            @foreach(['ca1' => 'ca1_score', 'ca2' => 'ca2_score', 'ca3' => 'ca3_score', 'exam' => 'exam_score'] as $key => $column)
                                <td class="num">
                                    @if($maxima[$key] > 0)
                                        @if(! $locked)<input data-score class="sheet-input" type="number" step="0.01" min="0" max="{{ $maxima[$key] }}" name="rows[{{ $row['subject']->id }}][{{ $key }}]" value="{{ $result ? rtrim(rtrim(number_format((float) $result->$column, 2, '.', ''), '0'), '.') : '' }}" aria-label="{{ $row['subject']->name }} {{ strtoupper($key) }} (max {{ $maxima[$key] }})">
                                        @else{{ $result ? rtrim(rtrim(number_format((float) $result->$column, 2, '.', ''), '0'), '.') : '—' }}@endif
                                        <div class="muted" style="font-size:.68rem">/{{ $maxima[$key] }}</div>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="num sheet-total" data-total>{{ $result && $offered ? rtrim(rtrim(number_format((float) $result->total_score, 2, '.', ''), '0'), '.') : '—' }}</td>
                            <td class="num"><span class="grade-badge{{ $result && $result->grade === $failGrade ? ' is-fail' : '' }}" data-grade>{{ $result ? ($offered ? $result->grade : 'N/O') : '—' }}</span></td>
                            <td>@if($result)<span class="pill {{ $result->status === 'published' ? 'pill--ok' : 'pill--warn' }}">{{ ucfirst($result->status) }}</span>@else<span class="pill">Not entered</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><p class="empty-state" style="margin:.6rem 0">No subjects are set up for this class yet. Add subjects in the admin console (Results setup &gt; Subjects).</p></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($traits->isNotEmpty())
        <div class="panel">
            <div class="panel__head"><h2>Skills and behaviour</h2><span class="muted" style="font-size:.85rem">1 = Poor &middot; 2 = Fair &middot; 3 = Good &middot; 4 = Very good &middot; 5 = Excellent</span></div>
            <div class="table-wrap">
                <table class="table rating-grid">
                    <thead><tr><th>Assessment</th>@foreach($labels as $value => $label)<th>{{ $value }} <span style="font-weight:500;text-transform:none;letter-spacing:0">({{ $label }})</span></th>@endforeach</tr></thead>
                    <tbody>
                    @foreach($traits as $trait)
                        <tr><td>{{ $trait->name }}</td>@foreach($labels as $value => $label)<td><input type="radio" name="ratings[{{ $trait->id }}]" value="{{ $value }}" @checked((int) ($ratings[$trait->id] ?? 0) === $value) aria-label="{{ $trait->name }}: {{ $label }}"></td>@endforeach</tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="panel"><div class="panel__body form-grid form-grid--2">
            <label class="field span-2">Class teacher’s remark<textarea name="teacher_remark" rows="3" maxlength="1000">{{ old('teacher_remark', $remark?->teacher_remark) }}</textarea></label>
            @if($isAdmin)<label class="field span-2">Principal’s remark<textarea name="principal_remark" rows="3" maxlength="1000">{{ old('principal_remark', $remark?->principal_remark) }}</textarea></label>@endif
        </div></div>

        <div class="sticky-actions">
            <span class="muted" style="font-size:.88rem">Saved scores stay <strong>pending</strong> until a results administrator publishes them.</span>
            <button type="submit" class="btn btn--maroon">Save result sheet</button>
        </div>
    </form>
</div></div>
@endsection
