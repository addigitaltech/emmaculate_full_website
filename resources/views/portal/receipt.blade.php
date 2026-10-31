<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt {{ $receipt->receipt_number }}</title>
<style>
@page { size: A4 portrait; margin: 12mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1b1f2a; background: #fff; }
.toolbar { max-width: 760px; margin: 12px auto; text-align: right; }
.toolbar a, .toolbar button { display: inline-block; padding: 8px 14px; border: 0; border-radius: 4px; background: #7a141a; color: #fff; text-decoration: none; font-size: 12px; cursor: pointer; font-family: inherit; }
.sheet { max-width: 760px; margin: 0 auto; padding: 16px 20px; border: 1px solid #cfc8bf; }
table { border-collapse: collapse; width: 100%; }
h1 { margin: 0; font-size: 18px; color: #041433; text-transform: uppercase; }
.muted { color: #566070; }
.title { text-align: center; margin: 12px 0; font-size: 15px; font-weight: bold; letter-spacing: 2px; color: #7a141a; }
.grid td { padding: 5px 6px; border: 1px solid #e3ddd6; }
.grid th { padding: 5px 6px; border: 1px solid #cfc8bf; background: #f1f1f1; text-align: left; font-size: 10px; }
.num { text-align: right; }
.total td { font-weight: bold; background: #f9f9f9; }
.stamp { display: inline-block; border: 2px solid #1d7a3a; color: #1d7a3a; padding: 3px 10px; font-weight: bold; letter-spacing: 1px; }
@media print { .toolbar { display: none !important; } .sheet { border: 0; padding: 0; } }
</style>
</head>
<body>
@if(! $isPdf)<div class="toolbar"><button type="button" onclick="window.print()">Print receipt</button> <a href="{{ route('receipts.show', ['receipt' => $receipt->receipt_number, 'download' => 'pdf']) }}">Download PDF</a></div>@endif
<div class="sheet">
    <table><tr>
        <td style="width:80px">@if($isPdf && $logoPath)<img src="file://{{ $logoPath }}" style="height:64px">@elseif(! $isPdf && $logoUrl)<img src="{{ $logoUrl }}" alt="" style="height:64px">@endif</td>
        <td><h1>{{ $settings->school_name }}</h1><div class="muted">{{ $settings->address }}@if($settings->phone_primary) &middot; {{ $settings->phone_primary }}@endif @if($settings->email) &middot; {{ $settings->email }}@endif</div></td>
        <td style="text-align:right"><span class="stamp">PAID</span></td>
    </tr></table>
    <div class="title">OFFICIAL RECEIPT</div>
    <table class="grid">
        <tr><th style="width:28%">Receipt number</th><td>{{ $receipt->receipt_number }}</td><th style="width:20%">Date</th><td>{{ ($transaction->verified_at ?? $receipt->issued_at)?->format('M j, Y g:i A') }}</td></tr>
        <tr><th>Student</th><td><strong>{{ $student?->fullName() }}</strong></td><th>Student ID</th><td>{{ $student?->student_number }}</td></tr>
        <tr><th>Class</th><td>{{ $student?->schoolClass?->name }}@if($student?->arm) ({{ $student->arm->name }})@endif</td><th>Paid by</th><td>{{ $transaction->initiator?->name ?? '—' }}</td></tr>
        <tr><th>Payment method</th><td>{{ ucfirst($transaction->gateway) }}</td><th>Reference</th><td>{{ $transaction->reference }}</td></tr>
    </table>
    <br>
    <table class="grid">
        <tr><th>Fee</th><th class="num">Fee total</th><th class="num">Paid now</th></tr>
        @foreach($items as $item)
            <tr><td>{{ $item['fee'] }}@if($item['note'])<div class="muted" style="font-size:9px">{{ $item['note'] }}</div>@endif</td><td class="num">{{ $transaction->currency }} {{ number_format($item['fee_total'], 2) }}</td><td class="num">{{ $transaction->currency }} {{ number_format($item['amount'], 2) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="2" class="num">Total received</td><td class="num">{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</td></tr>
    </table>
    <p style="margin-top:10px">Amount still owing for this student after this payment: <strong>{{ $transaction->currency }} {{ number_format($balance['balance'], 2) }}</strong></p>
    <p class="muted" style="margin-top:18px;font-size:9px">This receipt was produced by the school's payment system after the payment was confirmed with the payment provider. Keep it for your records.</p>
</div>
</body>
</html>
