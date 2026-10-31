@php($student = $transaction->student)
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:auto;color:#1b1f2a">
    <h2 style="color:#7a141a;margin-bottom:4px">New payment received</h2>
    <table style="border-collapse:collapse;width:100%;font-size:14px">
        <tr><td style="padding:6px 0;color:#566070">Student</td><td style="padding:6px 0"><strong>{{ $student?->fullName() }}</strong> ({{ $student?->student_number }})</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Class</td><td style="padding:6px 0">{{ $student?->schoolClass?->name }}@if($student?->arm) &middot; {{ $student->arm->name }}@endif</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Amount</td><td style="padding:6px 0"><strong>{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</strong> via {{ ucfirst($transaction->gateway) }}</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Receipt / reference</td><td style="padding:6px 0">{{ $transaction->receipt?->receipt_number }} / {{ $transaction->reference }}</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Paid by</td><td style="padding:6px 0">{{ $transaction->initiator?->name ?? '—' }}</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Student still owes</td><td style="padding:6px 0"><strong>{{ $transaction->currency }} {{ number_format((float) $balance['balance'], 2) }}</strong></td></tr>
    </table>
    @if($historyUrl)<p style="margin-top:16px"><a href="{{ $historyUrl }}">Open the payment history</a> to see every payment and who still owes.</p>@endif
</div>
