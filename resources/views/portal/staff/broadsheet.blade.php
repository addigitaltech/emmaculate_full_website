@extends('site.layout')
@section('title', 'Class broadsheet | '.$settings->short_name)
@section('content')
@include('portal.staff._head', ['title' => 'Class results broadsheet', 'subtitle' => 'Review score lines, subject averages and positions for a selected session, term, class and arm', 'current' => 'broadsheet'])
<style>
    .broadsheet-table-wrap{overflow-x:auto}.broadsheet-table{width:100%;border-collapse:collapse;font-size:.78rem;min-width:1050px}
    .broadsheet-table th,.broadsheet-table td{border:1px solid #d6dce5;padding:.38rem .42rem;text-align:center;vertical-align:middle;white-space:nowrap}
    .broadsheet-table th{background:#102744;color:#fff;font-weight:700}.broadsheet-table td:first-child{text-align:left;min-width:140px;white-space:normal}
    .broadsheet-table small{display:block;color:#68768a;font-size:.68rem}.broadsheet-table tbody tr:nth-child(even){background:#f6f8fb}.broadsheet-table .empty{text-align:center;padding:1rem;color:#68768a}
    @media print{.no-print,.portal-head,.portal-nav,.site-header,.site-footer{display:none!important}.portal-body{padding:0!important}.site-container{max-width:none!important;padding:0!important}.panel{border:0!important;box-shadow:none!important}.broadsheet-table{font-size:7pt;min-width:0}.broadsheet-table th,.broadsheet-table td{padding:2px 3px}.broadsheet-table-wrap{overflow:visible}}
</style>
<div class="portal-body"><div class="site-container">
    <div class="panel no-print"><div class="panel__body">
        <form method="get" action="{{ route('staff.results.broadsheet') }}" class="toolbar">
            <label class="field">Session
                <select name="session"><option value="">Choose session</option>@foreach($sessions as $option)<option value="{{ $option->id }}"@if((int) $sessionId === (int) $option->id) selected @endif>{{ $option->name }}</option>@endforeach</select>
            </label>
            <label class="field">Term
                <select name="term"><option value="">Choose term</option>@foreach($sessions as $option)@foreach($option->terms as $termOption)<option value="{{ $termOption->id }}"@if($term && (int) $term->id === (int) $termOption->id) selected @endif>{{ $option->name }} · {{ $termOption->name }}</option>@endforeach @endforeach</select>
            </label>
            <label class="field">Class
                <select name="class"><option value="">Choose class</option>@foreach($classes as $classOption)<option value="{{ $classOption->id }}"@if($class && (int) $class->id === (int) $classOption->id) selected @endif>{{ $classOption->name }}</option>@endforeach</select>
            </label>
            <label class="field">Arm
                <select name="arm"><option value="">All arms / class-wide</option>@foreach(($class?->arms ?? collect()) as $armOption)<option value="{{ $armOption->id }}"@if((int) $armId === (int) $armOption->id) selected @endif>{{ $armOption->name }}</option>@endforeach</select>
            </label>
            <button class="btn btn--navy" type="submit">Load broadsheet</button>
        </form>
        @if(! $isAdmin)<p class="muted" style="margin:.75rem 0 0">Teacher view is limited to your assigned class/arm and subjects. The assigned-subject average is not the full-class overall average, and overall rank is intentionally not shown.</p>@endif
    </div></div>

    @if($ready)
        <div class="panel"><div class="panel__head" style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap">
            <div><h2>{{ $class->name }}@if($armId) · {{ $class->arms->firstWhere('id', $armId)?->name }}@else · All arms @endif</h2><p class="muted" style="margin:.25rem 0 0">{{ $term->session?->name }} · {{ $term->name }} @if($subjects->isNotEmpty()) · {{ $subjects->count() }} subject(s)@endif</p></div>
            <div class="no-print" style="display:flex;gap:.5rem;flex-wrap:wrap"><button type="button" class="btn btn--navy" onclick="window.print()">Print</button><a class="btn btn--green" href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}">Download PDF</a></div>
        </div><div class="panel__body">
            @include('portal.staff._broadsheet-table')
            <p class="muted" style="font-size:.8rem;margin:.75rem 0 0">Averages and positions use offered result lines in the selected class/arm. Tied scores share a competition position and the next position is skipped. Missing scores are not treated as zero.</p>
        </div></div>
    @else
        <div class="panel"><div class="panel__body"><p class="empty-state" style="margin:0">Choose a session, term and class to load the broadsheet. Select an arm to narrow the roster.</p></div></div>
    @endif
</div></div>
@endsection
