<div class="panel">
    <div class="cap">Result analysis</div>
    <table class="stat">
        <tr><td>Total student in {{ $reportClass?->name ?: 'class' }}</td><td class="r">{{ $summary['class_total'] ?: '—' }}</td></tr>
        <tr><td>Class term position</td><td class="r">{{ $ord($summary['class_position']) }}</td></tr>
        @if($reportArm)
            <tr><td>Class arm position</td><td class="r">{{ $ord($summary['arm_position']) }}</td></tr>
            <tr><td>Total student in {{ $reportClass?->name ?: 'class' }} ({{ $reportArm->name }})</td><td class="r">{{ $summary['arm_total'] ?: '—' }}</td></tr>
        @endif
        <tr><td>Total term score</td><td class="r">{{ $fmt($summary['total_score']) }}</td></tr>
        <tr><td>Average term score</td><td class="r">{{ number_format($summary['average'], 2) }}% ({{ $summary['overall']['grade'] }})</td></tr>
        @if($showCumulative)
            <tr><td>Cumulative total score</td><td class="r">{{ $fmt($summary['cumulative_total']) }}</td></tr>
            <tr><td>Cumulative average</td><td class="r">{{ number_format($summary['cumulative_average'], 2) }}% ({{ $summary['cumulative_grade'] }})</td></tr>
            <tr><td>Cumulative position</td><td class="r">{{ $ord($summary['cumulative_position']) }}</td></tr>
        @endif
        <tr><td>Number of subjects offered</td><td class="r">{{ $summary['offered'] }}</td></tr>
        <tr><td>Number of subjects passed (pass mark {{ $fmt($summary['pass_mark']) }})</td><td class="r">{{ $summary['passed'] }}</td></tr>
        <tr><td>Number of subjects failed</td><td class="r">{{ $summary['failed'] }}</td></tr>
    </table>
</div>
