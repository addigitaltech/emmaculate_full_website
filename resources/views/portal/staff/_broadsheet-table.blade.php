@php
    $fmt = fn ($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp
<div class="broadsheet-table-wrap">
    <table class="broadsheet-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>{{ $isAdmin ? 'Overall total' : 'Assigned-subject total' }}</th>
                <th>{{ $isAdmin ? 'Overall average' : 'Assigned-subject average' }}</th>
                <th>Overall position</th>
                <th>Subject</th>
                <th>CA 1</th><th>CA 2</th><th>CA 3</th><th>Exam</th>
                <th>Subject total</th><th>Grade</th><th>Subject average</th>
                <th>Highest / lowest</th><th>Subject position</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @forelse($row['subjects'] as $subjectRow)
                    <tr>
                        <td>@if($loop->first)<strong>{{ $row['student']->last_name }}, {{ $row['student']->first_name }} {{ $row['student']->other_names }}</strong><small>{{ $row['student']->student_number }}</small>@endif</td>
                        <td>@if($loop->first){{ $fmt($row['total']) }}@endif</td>
                        <td>@if($loop->first){{ $fmt($row['average']) }}@endif</td>
                        <td>@if($loop->first){{ $isAdmin ? ($row['position'] ?? '—') : '—' }}@endif</td>
                        <td>{{ $subjectRow['subject']->name }}</td>
                        <td>{{ $fmt($subjectRow['ca1']) }}</td><td>{{ $fmt($subjectRow['ca2']) }}</td><td>{{ $fmt($subjectRow['ca3']) }}</td><td>{{ $fmt($subjectRow['exam']) }}</td>
                        <td><strong>{{ $subjectRow['total'] !== null ? $fmt($subjectRow['total']) : ($subjectRow['not_offered'] ? 'N/O' : '—') }}</strong></td>
                        <td>{{ $subjectRow['grade'] ?: '—' }}</td>
                        <td>{{ $fmt($subjectRow['average']) }}</td>
                        <td>{{ $subjectRow['population'] ? $fmt($subjectRow['highest']).' / '.$fmt($subjectRow['lowest']) : '—' }}</td>
                        <td>{{ $subjectRow['position'] ? $subjectRow['position'].' of '.$subjectRow['population'] : '—' }}</td>
                        <td>{{ $subjectRow['status'] ? ucfirst($subjectRow['status']) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td><strong>{{ $row['student']->last_name }}, {{ $row['student']->first_name }} {{ $row['student']->other_names }}</strong><small>{{ $row['student']->student_number }}</small></td>
                        <td>{{ $fmt($row['total']) }}</td><td>{{ $fmt($row['average']) }}</td><td>{{ $isAdmin ? ($row['position'] ?? '—') : '—' }}</td>
                        <td colspan="11">No applicable subjects or scores in this scope.</td>
                    </tr>
                @endforelse
            @empty
                <tr><td colspan="15" class="empty">No students or result rows are available for this selection.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
