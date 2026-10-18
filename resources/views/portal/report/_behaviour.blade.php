@if($ratings->isNotEmpty())
<div class="panel">
    <div class="cap">Student's skills &amp; behaviour</div>
    <table class="ratings">
        <tr><th class="l">Assessment</th>@foreach($labels as $value => $label)<th>{{ $value }}<br>({{ $label }})</th>@endforeach</tr>
        @foreach($ratings as $rating)
            <tr><td class="l">{{ $rating->trait?->name }}</td>@foreach($labels as $value => $label)<td>{{ (int) $rating->rating === $value ? '✓' : '–' }}</td>@endforeach</tr>
        @endforeach
    </table>
</div>
@endif
