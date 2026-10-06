@php
    $showCa2 = collect($rows)->contains(fn ($row) => ($row['maxima']['ca2'] ?? 0) > 0);
    $showCa3 = collect($rows)->contains(fn ($row) => ($row['maxima']['ca3'] ?? 0) > 0);
    $logoSrc = $logoPath ? ($isPdf ? 'file://'.$logoPath : asset('storage/'.$settings->logo_path)) : null;
    $photoSrc = $photoPath ? ($isPdf ? 'file://'.$photoPath : $student->photo?->url()) : null;
    $failGrade = collect($bands)->sortBy('min_score')->first()['grade'] ?? 'F';
    $labels = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very Good', 5 => 'Excellent'];
    $hasPending = collect($rows)->contains(fn ($row) => $row['status'] !== 'published');
    $fmt = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Report · {{ $student->fullName() }} · {{ $term->name }}</title>
<style>
@page { size: A4 landscape; margin: 10mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 10px; color: #1b1f2a; background: #fff; }
.toolbar { max-width: 1080px; margin: 12px auto; text-align: right; }
.toolbar a, .toolbar button { display: inline-block; padding: 8px 14px; border: 0; border-radius: 4px; background: #7a141a; color: #fff; text-decoration: none; font-size: 12px; cursor: pointer; font-family: inherit; }
.banner { max-width: 1080px; margin: 0 auto 8px; padding: 8px 12px; background: #fdf0d2; color: #7a4d00; border: 1px solid #f4d894; font-size: 11px; }
.sheet { max-width: 1080px; margin: 0 auto; padding: 14px 18px; border: 1px solid #cfc8bf; }
table { border-collapse: collapse; width: 100%; }
.head td { vertical-align: middle; }
.school { text-align: center; }
.school h1 { margin: 0; font-size: 20px; color: #041433; text-transform: uppercase; letter-spacing: .5px; }
.school .motto { margin: 2px 0; font-style: italic; color: #7a141a; }
.school .meta { margin: 2px 0; color: #566070; font-size: 9px; }
.rule { border-top: 2px solid #041433; margin: 8px 0; }
.title { text-align: center; font-weight: bold; font-size: 12px; text-transform: uppercase; margin: 6px 0 8px; color: #7a141a; letter-spacing: .5px; }
.info td { padding: 2px 6px; font-size: 10px; }
.info td.k { color: #566070; width: 17%; }
.info td.v { font-weight: bold; width: 33%; }
.grid th { background: #041433; color: #fff; padding: 5px 4px; border: 1px solid #041433; font-size: 9px; text-align: center; }
.grid th.l, .grid td.l { text-align: left; }
.grid td { padding: 4px; border: 1px solid #cfc8bf; text-align: center; }
.grid tr.alt td { background: #f7f2ee; }
.grid td.sub { font-weight: bold; text-align: left; }
.grade { font-weight: bold; color: #041433; }
.grade.fail { color: #b3121f; }
.two td.col { vertical-align: top; }
.box { border: 1px solid #cfc8bf; }
.box th { background: #f7f2ee; text-align: left; padding: 4px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: .4px; color: #041433; border-bottom: 1px solid #cfc8bf; }
.box td { padding: 4px 6px; border-bottom: 1px solid #ece7e1; }
.box td.r { text-align: right; font-weight: bold; }
.bars td { padding: 2px 4px; font-size: 8px; vertical-align: middle; }
.bar { height: 6px; display: block; }
.bar.me { background: #0a1f4a; }
.bar.avg { background: #e2a52c; margin-top: 2px; }
.key { font-size: 8px; color: #566070; margin: 3px 0 0; }
.ratings th, .ratings td { padding: 3px 5px; border: 1px solid #cfc8bf; text-align: center; font-size: 9px; }
.ratings th { background: #f7f2ee; }
.ratings td.l, .ratings th.l { text-align: left; }
.remark { border: 1px solid #cfc8bf; padding: 6px 8px; min-height: 34px; }
.remark b { color: #041433; font-size: 9px; text-transform: uppercase; }
.sign td { padding-top: 22px; text-align: center; font-size: 9px; color: #566070; }
.sign td span { display: block; border-top: 1px solid #555; padding-top: 3px; margin: 0 18px; }
.foot { margin: 8px 0 0; text-align: center; color: #8a8580; font-size: 8px; }
@media print { .toolbar, .banner { display: none !important; } .sheet { border: 0; padding: 0; max-width: none; } }
</style>
</head>
<body>
@if(! $isPdf)
    <div class="toolbar"><button type="button" data-print>Print report</button> <a href="{{ route('reports.show', ['student' => $student->id, 'term' => $term->id]) }}?download=pdf">Download PDF</a></div>
    @if($includeUnpublished && $hasPending)<div class="banner">Staff preview: this report includes results that are still <strong>pending</strong>. Students and parents only ever see published results.</div>@endif
@endif
<div class="sheet">
    <table class="head"><tr>
        <td style="width:90px">@if($logoSrc)<img src="{{ $logoSrc }}" alt="" style="height:64px;width:auto">@endif</td>
        <td class="school"><h1>{{ $settings->school_name }}</h1>@if($settings->motto)<p class="motto">{{ $settings->motto }}</p>@endif<p class="meta">{{ $settings->address }}@if($settings->phone_primary) &middot; {{ $settings->phone_primary }}@endif @if($settings->email) &middot; {{ $settings->email }}@endif</p></td>
        <td style="width:90px;text-align:right">@if($photoSrc)<img src="{{ $photoSrc }}" alt="" style="height:76px;width:auto;border:1px solid #cfc8bf">@endif</td>
    </tr></table>
    <div class="rule"></div>
    <div class="title">{{ $term->name }} report &mdash; {{ $term->session?->name }} academic session</div>
    <table class="info">
        <tr><td class="k">Full name</td><td class="v">{{ strtoupper($student->last_name) }} {{ trim($student->first_name.' '.$student->other_names) }}</td><td class="k">Class (arm)</td><td class="v">{{ $student->schoolClass?->name }}@if($student->arm) ({{ $student->arm->name }})@endif</td></tr>
        <tr><td class="k">Gender</td><td class="v">{{ $student->gender ?: '—' }}</td><td class="k">Student ID</td><td class="v">{{ $student->student_number }}</td></tr>
        <tr><td class="k">Date of birth (age)</td><td class="v">{{ $student->date_of_birth ? $student->date_of_birth->format('Y-m-d').' ('.$student->date_of_birth->age.' years)' : '—' }}</td><td class="k">Report for</td><td class="v">{{ $term->name }}, {{ $term->session?->name }}</td></tr>
    </table>

    <table class="grid" style="margin-top:8px">
        <thead><tr><th class="l">Subject</th><th>CA 1</th>@if($showCa2)<th>CA 2</th>@endif @if($showCa3)<th>CA 3</th>@endif<th>Exam</th><th>Total</th><th>Class avg</th><th>Highest</th><th>Lowest</th><th>Position</th><th>Grade</th><th class="l">Remark</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="sub">{{ $row['subject'] }}</td>
                <td>{{ $fmt($row['ca1']) }}</td>@if($showCa2)<td>{{ $fmt($row['ca2']) }}</td>@endif @if($showCa3)<td>{{ $fmt($row['ca3']) }}</td>@endif
                <td>{{ $fmt($row['exam']) }}</td><td><strong>{{ $fmt($row['total']) }}</strong></td>
                <td>{{ $fmt($row['average']) }}</td><td>{{ $fmt($row['highest']) }}</td><td>{{ $fmt($row['lowest']) }}</td>
                <td>{{ $row['position'] }}/{{ $row['population'] }}</td>
                <td class="grade{{ $row['grade'] === $failGrade ? ' fail' : '' }}">{{ $row['grade'] }}</td>
                <td class="l">{{ $row['remark'] }}</td>
            </tr>
        @empty
            <tr><td colspan="12" style="padding:14px;color:#566070">No results are available for this term yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="two" style="margin-top:10px"><tr>
        <td class="col" style="width:38%;padding-right:10px">
            <table class="box">
                <tr><th colspan="2">Result summary</th></tr>
                <tr><td>Total students in {{ $student->schoolClass?->name }}</td><td class="r">{{ $summary['class_total'] ?: '—' }}</td></tr>
                <tr><td>Class term position</td><td class="r">{{ $summary['class_position'] ?: '—' }}</td></tr>
                @if($student->arm)<tr><td>Total students in {{ $student->schoolClass?->name }} ({{ $student->arm->name }})</td><td class="r">{{ $summary['arm_total'] ?: '—' }}</td></tr>
                <tr><td>Class arm position</td><td class="r">{{ $summary['arm_position'] ?: '—' }}</td></tr>@endif
                <tr><td>Total term score</td><td class="r">{{ $fmt($summary['total_score']) }}</td></tr>
                <tr><td>Average term score</td><td class="r">{{ number_format($summary['average'], 2) }}% ({{ $summary['overall']['grade'] }})</td></tr>
                <tr><td>Subjects offered</td><td class="r">{{ $summary['offered'] }}</td></tr>
                <tr><td>Subjects passed (pass mark {{ $fmt($summary['pass_mark']) }})</td><td class="r">{{ $summary['passed'] }}</td></tr>
                <tr><td>Subjects failed</td><td class="r">{{ $summary['failed'] }}</td></tr>
            </table>
        </td>
        <td class="col" style="width:31%;padding-right:10px">
            <table class="box">
                <tr><th>Class performance (score vs class average)</th></tr>
                <tr><td>
                    <table class="bars">
                    @foreach($rows as $row)
                        <tr><td style="width:34%">{{ \Illuminate\Support\Str::limit($row['subject'], 18) }}</td>
                        <td><span class="bar me" style="width: {{ max(2, min(100, (float) $row['total'])) }}%"></span><span class="bar avg" style="width: {{ max(2, min(100, (float) $row['average'])) }}%"></span></td></tr>
                    @endforeach
                    </table>
                    <p class="key"><span style="color:#0a1f4a">&#9632;</span> Student &nbsp; <span style="color:#e2a52c">&#9632;</span> Class average</p>
                </td></tr>
            </table>
        </td>
        <td class="col" style="width:31%">
            @if($ratings->isNotEmpty())
            <table class="ratings">
                <tr><th class="l">Skills &amp; behaviour</th>@foreach($labels as $value => $label)<th>{{ $value }}</th>@endforeach</tr>
                @foreach($ratings as $rating)
                    <tr><td class="l">{{ $rating->trait?->name }}</td>@foreach($labels as $value => $label)<td>{{ (int) $rating->rating === $value ? '✓' : '' }}</td>@endforeach</tr>
                @endforeach
            </table>
            <p class="key">1 Poor &middot; 2 Fair &middot; 3 Good &middot; 4 Very good &middot; 5 Excellent</p>
            @endif
        </td>
    </tr></table>

    <table style="margin-top:8px"><tr>
        <td style="width:50%;padding-right:6px"><div class="remark"><b>Class teacher’s remark</b><br>{{ $teacherRemark ?: '—' }}</div></td>
        <td style="width:50%;padding-left:6px"><div class="remark"><b>Principal’s remark</b><br>{{ $principalRemark ?: '—' }}</div></td>
    </tr></table>
    <p class="key" style="margin-top:6px">Grading: @foreach($bands as $band){{ $band['grade'] }} = {{ $band['min_score'] }}–{{ $band['max_score'] }}@if(! $loop->last) &middot; @endif @endforeach</p>
    <table class="sign"><tr><td><span>Class teacher</span></td><td><span>Principal</span></td><td><span>Parent / Guardian</span></td></tr></table>
    <p class="foot">Generated {{ now()->format('Y-m-d H:i') }} for {{ $student->fullName() }}</p>
</div>
@if(! $isPdf)@vite(['resources/js/app.js'])@endif
</body>
</html>
