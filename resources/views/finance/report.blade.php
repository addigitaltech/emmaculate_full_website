@php($money = fn ($value) => number_format((float) $value, 2))
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment history</title>
<style>
@page { size: A4 landscape; margin: 10mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1b1f2a; background: #fff; }
.toolbar { max-width: 1100px; margin: 12px auto; text-align: right; }
.toolbar a, .toolbar button { display: inline-block; padding: 8px 14px; border: 0; border-radius: 4px; background: #7a141a; color: #fff; text-decoration: none; font-size: 12px; cursor: pointer; font-family: inherit; }
.sheet { max-width: 1100px; margin: 0 auto; padding: 8px 12px; }
table { border-collapse: collapse; width: 100%; }
h1 { margin: 0; font-size: 17px; color: #041433; text-transform: uppercase; }
h2 { margin: 16px 0 6px; font-size: 12px; color: #7a141a; }
.muted { color: #566070; }
th { background: #f1f1f1; text-align: left; padding: 4px 5px; border: 1px solid #cfc8bf; font-size: 9px; }
td { padding: 4px 5px; border: 1px solid #e3ddd6; vertical-align: top; }
.num { text-align: right; white-space: nowrap; }
.box td { border: 0; padding: 2px 8px 2px 0; }
tfoot td { font-weight: bold; background: #f9f9f9; }
@media print { .toolbar { display: none !important; } }
</style>
</head>
<body>
@if(! $isPdf)<div class="toolbar"><button type="button" onclick="window.print()">Print</button> <a href="{{ $pdfUrl }}">Download PDF</a> <a href="{{ route('finance.report', array_filter($filters, fn ($v) => $v !== null && $v !== '') + ['download' => 'csv']) }}">Download spreadsheet (CSV)</a></div>@endif
<div class="sheet">
    <table class="box"><tr>
        <td style="width:70px">@if($isPdf && $logoPath)<img src="file://{{ $logoPath }}" style="height:50px">@endif</td>
        <td><h1>{{ $settings->school_name }}</h1><div class="muted">Payment history and outstanding fees &middot; printed {{ now()->format('M j, Y g:i A') }}</div></td>
        <td class="num muted">
            @if($filters['from'] ?? null) From {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') }}@endif
            @if($filters['to'] ?? null) to {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') }}@endif
            @if($session)<br>Session {{ $session->name }}@endif
            @if($class)<br>{{ $class->name }}@if($arm) {{ $arm->name }}@endif @endif
            <br>Showing: {{ ucfirst($filters['status']) }} payments
        </td>
    </tr></table>

    <h2>Summary</h2>
    <table class="box" style="width:auto"><tr>
        <td>Verified payments: <strong>{{ $totals['paid_count'] }}</strong></td>
        <td>Total received: <strong>NGN {{ $money($totals['paid_total']) }}</strong></td>
        <td>Students still owing: <strong>{{ $debtTotals['students'] }}</strong></td>
        <td>Total still owing: <strong>NGN {{ $money($debtTotals['balance']) }}</strong></td>
    </tr></table>
    <table style="width:auto;margin-top:6px"><tr>
        <td style="vertical-align:top;border:0;padding:0 18px 0 0">
            <table><tr><th>Method</th><th class="num">Payments</th><th class="num">Total (NGN)</th></tr>
            @foreach($byGateway as $row)<tr><td>{{ $row['gateway'] }}</td><td class="num">{{ $row['count'] }}</td><td class="num">{{ $money($row['total']) }}</td></tr>@endforeach</table>
        </td>
        <td style="vertical-align:top;border:0;padding:0">
            <table><tr><th>Class</th><th class="num">Payments</th><th class="num">Total (NGN)</th></tr>
            @foreach($byClass as $row)<tr><td>{{ $row['class'] }}</td><td class="num">{{ $row['count'] }}</td><td class="num">{{ $money($row['total']) }}</td></tr>@endforeach</table>
        </td>
    </tr></table>

    <h2>Payments</h2>
    <table>
        <thead><tr><th>Date</th><th>Receipt</th><th>Student</th><th>Class</th><th>Paid by</th><th>Method</th><th>Fees covered</th><th>Status</th><th class="num">Amount (NGN)</th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $payment->receipt?->receipt_number ?? '—' }}<br><span class="muted">{{ $payment->reference }}</span></td>
                <td>{{ $payment->student?->fullName() }}<br><span class="muted">{{ $payment->student?->student_number }}</span></td>
                <td>{{ $payment->student?->schoolClass?->name }} {{ $payment->student?->arm?->name }}</td>
                <td>{{ $payment->initiator?->name ?? '—' }}</td>
                <td>{{ ucfirst($payment->gateway) }}</td>
                <td>@if($payment->allocations->isNotEmpty()){{ $payment->allocations->map(fn ($a) => ($a->feeAssignment?->feeType?->name ?? 'Fee').' '.$money($a->amount))->implode('; ') }}@else{{ $payment->feeAssignment?->feeType?->name }}@endif</td>
                <td>{{ ucfirst($payment->status) }}</td>
                <td class="num">{{ $money($payment->amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted">No payments match these filters.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr><td colspan="8" class="num">Total received (verified)</td><td class="num">{{ $money($totals['paid_total']) }}</td></tr></tfoot>
    </table>
    @if($payments->count() >= 5000)<p class="muted">Only the first 5,000 payments are shown. Narrow the dates to see the rest.</p>@endif

    <h2>Still owing (unpaid and part-paid fees)</h2>
    <table>
        <thead><tr><th>Student</th><th>Class</th><th>Unpaid fees (balance)</th><th class="num">Charged</th><th class="num">Paid</th><th class="num">Balance (NGN)</th></tr></thead>
        <tbody>
        @forelse($debts as $row)
            <tr><td>{{ $row['student']?->fullName() }}<br><span class="muted">{{ $row['student']?->student_number }}</span></td><td>{{ $row['class'] }}</td><td>{{ $row['fees'] }}</td><td class="num">{{ $money($row['due']) }}</td><td class="num">{{ $money($row['paid']) }}</td><td class="num"><strong>{{ $money($row['balance']) }}</strong></td></tr>
        @empty
            <tr><td colspan="6" class="muted">Nobody owes anything for these filters.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr><td colspan="3" class="num">Totals</td><td class="num">{{ $money($debtTotals['due']) }}</td><td class="num">{{ $money($debtTotals['paid']) }}</td><td class="num">{{ $money($debtTotals['balance']) }}</td></tr></tfoot>
    </table>
</div>
</body>
</html>
