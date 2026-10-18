@php
    $showCa2 = collect($rows)->contains(fn ($row) => ($row['maxima']['ca2'] ?? 0) > 0);
    $showCa3 = collect($rows)->contains(fn ($row) => ($row['maxima']['ca3'] ?? 0) > 0);
    $showCumulative = (int) $term->sequence > 1;
    $termSequences = collect($cumulativeTerms ?? [])->pluck('sequence')->map(fn ($sequence) => (int) $sequence)->values();
    $logoSrc = $logoPath ? ($isPdf ? 'file://'.$logoPath : asset('storage/'.$settings->logo_path)) : null;
    $photoSrc = $photoPath ? ($isPdf ? 'file://'.$photoPath : $student->photo?->url()) : null;
    $failGrade = collect($bands)->sortBy('min_score')->first()['grade'] ?? 'F';
    $labels = [1 => 'Fair', 2 => 'Pass', 3 => 'Good', 4 => 'Very Good', 5 => 'Excellent'];
    $hasPending = collect($rows)->contains(fn ($row) => $row['status'] !== 'published');
    $fmt = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $ord = function ($number): string {
        $number = (int) $number;
        if ($number <= 0) {
            return '—';
        }
        $suffix = in_array($number % 100, [11, 12, 13], true) ? 'th' : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$number % 10] ?? 'th');

        return $number.$suffix;
    };
    $termLabel = fn ($sequence) => [1 => '1st term', 2 => '2nd term', 3 => '3rd term'][(int) $sequence] ?? ((int) $sequence.'th term');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Report · {{ $student->fullName() }} · {{ $term->name }}</title>
<style>
@page { size: A4 portrait; margin: 8mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 9px; color: #1b1f2a; background: #fff; }
.toolbar { max-width: 794px; margin: 12px auto; text-align: right; }
.toolbar a, .toolbar button { display: inline-block; padding: 8px 14px; border: 0; border-radius: 4px; background: #7a141a; color: #fff; text-decoration: none; font-size: 12px; cursor: pointer; font-family: inherit; }
.banner { max-width: 794px; margin: 0 auto 8px; padding: 8px 12px; background: #fdf0d2; color: #7a4d00; border: 1px solid #f4d894; font-size: 11px; }
.sheet { width: 100%; max-width: 794px; margin: 0 auto; padding: 10px 12px; }
table { border-collapse: collapse; width: 100%; }
.head td { vertical-align: middle; }
.school h1 { margin: 0; font-size: 19px; color: #041433; text-transform: uppercase; letter-spacing: .4px; }
.school .motto { margin: 1px 0 3px; font-style: italic; font-size: 8px; color: #566070; }
.school .meta { margin: 1px 0; color: #1b1f2a; font-size: 9px; }
.rule { border-top: 1px solid #cfc8bf; margin: 7px 0; }
.bio td { padding: 2px 6px; font-size: 10px; vertical-align: top; }
.bio td.k { width: 27%; text-align: right; font-weight: bold; color: #1b1f2a; }
.bio td.v { width: 53%; }
.photo { text-align: right; }
.photo img { height: 84px; width: 70px; border: 1px solid #cfc8bf; }
.photo .ph { display: inline-block; height: 84px; width: 70px; border: 1px dashed #cfc8bf; text-align: center; font-size: 7px; color: #8a8580; padding-top: 34px; }
.panel { border: 1px solid #cfc8bf; margin-bottom: 8px; page-break-inside: avoid; }
.panel > .cap { background: #f1f1f1; border-bottom: 1px solid #cfc8bf; padding: 3px 5px; text-align: center; font-size: 7px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
.grid th { background: #f1f1f1; padding: 3px 2px; border: 1px solid #cfc8bf; font-size: 7px; text-align: center; }
.grid th.l, .grid td.l { text-align: left; }
.grid td { padding: 3px 2px; border: 1px solid #e3ddd6; text-align: center; font-size: 8px; }
.grid tr.alt td { background: #f9f9f9; }
.grid td.sub { font-weight: bold; text-align: left; text-transform: uppercase; font-size: 7.5px; }
.grid--wide th, .grid--wide td { font-size: 6.5px; padding: 3px 1px; }
.grid--wide td.sub { font-size: 7px; }
.grade { font-weight: bold; color: #041433; font-size: 10px; }
.grade.fail { color: #b3121f; }
.stat td { padding: 4px 6px; border-bottom: 1px solid #e3ddd6; font-size: 8px; text-transform: uppercase; }
.stat td.r { width: 38%; background: #f9f9f9; border-left: 1px solid #e3ddd6; }
.bars td { padding: 2px 4px; font-size: 6.5px; vertical-align: middle; text-transform: uppercase; }
.bar { height: 5px; display: block; }
.bar.me { background: #36b8b8; }
.bar.avg { background: #a98ee0; margin-top: 2px; }
.key { font-size: 7px; color: #566070; margin: 3px 0 0; padding: 0 5px 4px; }
.ratings th, .ratings td { padding: 3px 4px; border: 1px solid #e3ddd6; text-align: center; font-size: 7.5px; }
.ratings th { background: #f9f9f9; }
.ratings td.l, .ratings th.l { text-align: left; font-weight: bold; }
.remark { padding: 3px 6px; font-size: 7.5px; min-height: 14px; }
.sign td { padding-top: 24px; text-align: center; font-size: 8px; color: #566070; }
.sign td span { display: block; border-top: 1px solid #555; padding-top: 3px; margin: 0 14px; }
.foot { margin: 6px 0 0; text-align: center; color: #8a8580; font-size: 7px; }
@media print { .toolbar, .banner { display: none !important; } .sheet { padding: 0; max-width: none; } }
</style>
</head>
<body>
@if(! $isPdf)
    <div class="toolbar"><button type="button" data-print>Print report</button> <a href="{{ $pdfUrl ?? (route('reports.show', ['student' => $student->id, 'term' => $term->id]).'?download=pdf') }}">Download PDF</a></div>
    @if($includeUnpublished && $hasPending)<div class="banner">Staff preview: this report includes results that are still <strong>pending</strong>. Students and parents only ever see published results.</div>@endif
@endif
<div class="sheet">
    <table class="head"><tr>
        <td style="width:92px">@if($logoSrc)<img src="{{ $logoSrc }}" alt="" style="height:74px;width:auto">@endif</td>
        <td class="school"><h1>{{ $settings->school_name }}</h1>@if($settings->motto)<p class="motto">{{ $settings->motto }}</p>@endif<p class="meta"><strong>Address:</strong> {{ $settings->address }}</p>@if($settings->phone_primary)<p class="meta"><strong>Phone Number:</strong> {{ $settings->phone_primary }}</p>@endif @if($settings->email)<p class="meta"><strong>Email Address:</strong> {{ $settings->email }}</p>@endif</td>
    </tr></table>
    <div class="rule"></div>

    <table class="bio"><tr>
        <td style="width:80%;padding:0">
            <table class="bio">
                <tr><td class="k">Full Name (Gender):</td><td class="v"><strong>{{ strtoupper($student->last_name) }}</strong> {{ trim($student->first_name.' '.$student->other_names) }}@if($student->gender) ({{ $student->gender }})@endif</td></tr>
                <tr><td class="k">Student ID:</td><td class="v"><strong>{{ $student->student_number }}</strong></td></tr>
                <tr><td class="k">Date of Birth (Age):</td><td class="v">{{ $student->date_of_birth ? $student->date_of_birth->format('Y-m-d').' ('.$student->date_of_birth->age.' years of age)' : '—' }}</td></tr>
                <tr><td class="k">Student Class:</td><td class="v">{{ $reportClass?->name ?: '—' }}@if($reportArm) ({{ $reportArm->name }})@endif</td></tr>
                <tr><td class="k">Report Sheet For:</td><td class="v">{{ $term->name }}, {{ $term->session?->name }} Academic Session</td></tr>
            </table>
        </td>
        <td class="photo" style="width:20%">@if($photoSrc)<img src="{{ $photoSrc }}" alt="Passport photograph">@else<span class="ph">Passport photograph</span>@endif</td>
    </tr></table>
    <div class="rule"></div>

    @if($showCumulative)
        @include('portal.report._table')
        <table style="margin-top:2px"><tr>
            <td style="width:34%;vertical-align:top;padding-right:6px">@include('portal.report._analysis')</td>
            <td style="width:33%;vertical-align:top;padding-right:6px">@include('portal.report._graph')</td>
            <td style="width:33%;vertical-align:top">@include('portal.report._behaviour')</td>
        </tr></table>
        @include('portal.report._remarks')
    @else
        <table><tr>
            <td style="width:61%;vertical-align:top;padding-right:8px">
                @include('portal.report._table')
                @include('portal.report._analysis')
                @include('portal.report._remarks')
            </td>
            <td style="width:39%;vertical-align:top">
                @include('portal.report._graph')
                @include('portal.report._behaviour')
            </td>
        </tr></table>
    @endif

    <p class="key" style="padding:0">Grading: @foreach($bands as $band){{ $band['grade'] }} = {{ $band['min_score'] }}–{{ $band['max_score'] }}@if(! $loop->last) &middot; @endif @endforeach</p>
    <table class="sign"><tr><td><span>Class teacher@if($classTeacherName ?? null): {{ $classTeacherName }}@endif</span></td><td><span>Principal</span></td><td><span>Parent / Guardian</span></td></tr></table>
    <p class="foot">Generated {{ now()->format('Y-m-d H:i') }} for {{ $student->fullName() }}</p>
</div>
@if(! $isPdf)@vite(['resources/js/app.js'])@endif
</body>
</html>
