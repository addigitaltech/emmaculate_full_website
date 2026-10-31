@php($student = $transaction->student)
<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:auto;color:#1b1f2a">
    <h2 style="color:#7a141a;margin-bottom:4px">Payment received</h2>
    <p>Thank you. We have received and verified your payment for <strong>{{ $student?->fullName() }}</strong>@if($student?->student_number) ({{ $student->student_number }})@endif.</p>
    <table style="border-collapse:collapse;width:100%;font-size:14px">
        <tr><td style="padding:6px 0;color:#566070">Amount paid</td><td style="padding:6px 0"><strong>{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</strong></td></tr>
        <tr><td style="padding:6px 0;color:#566070">Receipt number</td><td style="padding:6px 0">{{ $transaction->receipt?->receipt_number }}</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Payment reference</td><td style="padding:6px 0">{{ $transaction->reference }}</td></tr>
        <tr><td style="padding:6px 0;color:#566070">Still owing for this student</td><td style="padding:6px 0"><strong>{{ $transaction->currency }} {{ number_format((float) $balance['balance'], 2) }}</strong></td></tr>
    </table>
    <p style="margin-top:16px">Your receipt is attached to this email. You can also print it again at any time from your portal@if($receiptUrl): <a href="{{ $receiptUrl }}">open receipt</a>@endif.</p>
    <p style="color:#8a8580;font-size:12px">If you did not make this payment, please contact the school office straight away.</p>
</div>
