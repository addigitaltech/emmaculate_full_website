<div class="panel">
    <div class="cap">{{ $showCumulative ? 'Termly and cumulative report' : 'Termly report' }}</div>
    <table class="grid{{ $showCumulative ? ' grid--wide' : '' }}">
        <thead><tr>
            <th class="l">Subject</th><th>CA 1</th>@if($showCa2)<th>CA 2</th>@endif @if($showCa3)<th>CA 3</th>@endif<th>Exam</th><th>Total</th>
            @if($showCumulative)@foreach($termSequences as $sequence)<th>{{ $termLabel($sequence) }}</th>@endforeach<th>Cumulative total</th><th>Cum. avg</th><th>Cum. grade</th>@endif
            <th>Class Avg</th><th>Class High</th><th>Class Low</th><th>Class Pos</th><th>Grade</th>
        </tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="sub">{{ $row['subject'] }}</td>
                <td>{{ $fmt($row['ca1']) }}</td>@if($showCa2)<td>{{ $fmt($row['ca2']) }}</td>@endif @if($showCa3)<td>{{ $fmt($row['ca3']) }}</td>@endif
                <td>{{ $fmt($row['exam']) }}</td><td><strong>{{ $fmt($row['total']) }}</strong></td>
                @if($showCumulative)
                    @foreach($termSequences as $sequence)<td>{{ ($row['term_totals'][$sequence] ?? null) === null ? '—' : $fmt($row['term_totals'][$sequence]) }}</td>@endforeach
                    <td><strong>{{ $fmt($row['cumulative_total']) }}</strong></td><td>{{ $fmt($row['cumulative_average']) }}%</td><td class="grade{{ $row['cumulative_grade'] === $failGrade ? ' fail' : '' }}">{{ $row['cumulative_grade'] }}</td>
                @endif
                <td>{{ $fmt($row['average']) }}</td><td>{{ $fmt($row['highest']) }}</td><td>{{ $fmt($row['lowest']) }}</td>
                <td>{{ $ord($row['position']) }}</td>
                <td class="grade{{ $row['grade'] === $failGrade ? ' fail' : '' }}">{{ $row['grade'] }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ 8 + (int) $showCa2 + (int) $showCa3 + ($showCumulative ? $termSequences->count() + 3 : 0) }}" style="padding:12px;color:#566070">No results are available for this term yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if($showCumulative)<p class="key">Cumulative figures use the {{ $includeUnpublished ? 'published or pending' : 'published' }} results saved for this student through {{ $term->name }}. Terms without a saved score are left out of the average.</p>@endif
</div>
