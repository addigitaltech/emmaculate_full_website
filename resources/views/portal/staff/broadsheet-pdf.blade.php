<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Class results broadsheet</title>
    <style>
        @page{size:A3 landscape;margin:9mm}*{box-sizing:border-box}body{font-family:DejaVu Sans,Arial,sans-serif;color:#172033;font-size:8px;margin:0}
        h1{font-size:16px;margin:0 0 4px;color:#102744}p{margin:3px 0 8px;color:#475569}.broadsheet-table{width:100%;border-collapse:collapse;font-size:7px}
        .broadsheet-table th,.broadsheet-table td{border:1px solid #cbd5e1;padding:3px 4px;text-align:center;vertical-align:middle}
        .broadsheet-table th{background:#102744;color:#fff;font-weight:bold}.broadsheet-table td:first-child{text-align:left;min-width:115px;white-space:normal}
        .broadsheet-table small{display:block;color:#475569;font-size:6px}.broadsheet-table tbody tr:nth-child(even){background:#f1f5f9}.broadsheet-table .empty{text-align:center;padding:10px}
        .footnote{font-size:7px;color:#475569;margin-top:8px}
    </style>
</head>
<body>
    <h1>{{ $settings->school_name }} — Class results broadsheet</h1>
    <p><strong>{{ $class->name }}@if($armId) · {{ $class->arms->firstWhere('id', $armId)?->name }}@else · All arms @endif</strong> &nbsp; | &nbsp; {{ $term->session?->name }} · {{ $term->name }} &nbsp; | &nbsp; Generated {{ now()->format('d M Y H:i') }}</p>
    @include('portal.staff._broadsheet-table')
    <p class="footnote">Averages and positions use offered result lines in the selected class/arm. Tied scores share a competition position and the next position is skipped. Missing scores are not treated as zero. @unless($isAdmin)Teacher view is limited to assigned subjects; overall rank is not shown.@endunless</p>
</body>
</html>
