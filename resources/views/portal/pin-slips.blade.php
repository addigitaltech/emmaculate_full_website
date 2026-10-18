<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student PIN slips</title>
<style>
@page { size: A4 portrait; margin: 10mm; }
body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1b1f2a; }
table.page { width: 100%; border-collapse: separate; border-spacing: 6px; }
td.slip { width: 50%; border: 1px dashed #666; padding: 8px 10px; vertical-align: top; height: 128px; }
.school { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #041433; }
.title { font-size: 8px; color: #566070; margin: 1px 0 6px; text-transform: uppercase; letter-spacing: .4px; }
.row { margin: 2px 0; }
.row span { color: #566070; display: inline-block; width: 78px; }
.pin { font-family: DejaVu Sans Mono, monospace; font-size: 20px; font-weight: bold; letter-spacing: 4px; color: #7a141a; margin: 4px 0 3px; }
.how { font-size: 7.5px; color: #566070; line-height: 1.35; }
</style>
</head>
<body>
@foreach($slips->chunk(10) as $page)
    <div @if(! $loop->last) style="page-break-after: always" @endif>
        <table class="page">
            @foreach($page->chunk(2) as $pair)
                <tr>
                    @foreach($pair as $slip)
                        <td class="slip">
                            <div class="school">{{ $settings->school_name }}</div>
                            <div class="title">Student portal PIN slip</div>
                            <div class="row"><span>Name</span><strong>{{ strtoupper($slip['student']->last_name) }}</strong> {{ trim($slip['student']->first_name.' '.$slip['student']->other_names) }}</div>
                            <div class="row"><span>Class</span>{{ $slip['student']->schoolClass?->name }}@if($slip['student']->arm) ({{ $slip['student']->arm->name }})@endif</div>
                            <div class="row"><span>Student ID</span><strong>{{ $slip['student']->student_number }}</strong></div>
                            <div class="pin">{{ $slip['pin'] }}</div>
                            <div class="how">Sign in at {{ $loginUrl }} with your Student ID, your surname in CAPITAL LETTERS and this PIN. Keep it private and do not share it.</div>
                        </td>
                    @endforeach
                    @if($pair->count() < 2)<td class="slip" style="border:0"></td>@endif
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
