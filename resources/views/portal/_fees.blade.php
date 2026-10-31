@php
    $money = fn ($value) => 'NGN '.number_format((float) $value, 2);
    $byStudent = $fees->groupBy('student_id');
    $grandBalance = $fees->sum(fn ($fee) => $fee->balance());
@endphp
<section class="mt-10" id="fees">
    <h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">School fees</h2>

    @if($errors->has('payment') || $errors->has('amount'))
        <div class="mt-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first('payment') ?: $errors->first('amount') }}</div>
    @endif

    @if($fees->isEmpty())
        <p class="mt-4 rounded-lg border border-dashed border-[var(--border)] p-5 text-sm text-[var(--ink-soft)]">Nothing is owing: there are no unpaid fees for this account.</p>
    @else
        <div class="mt-4 rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] p-5">
            <p class="text-sm text-[var(--ink-soft)]">Total still owing</p>
            <p class="font-display text-3xl font-semibold text-[var(--brand-800)]">{{ $money($grandBalance) }}</p>
            <p class="mt-1 text-xs text-[var(--ink-soft)]">@if($paymentMode === 'full')The school takes all fees of a student together in one payment.@else You may pay a fee in full or in installments (smallest payment {{ $money($minInstallment) }}, or the balance if it is less).@endif</p>
        </div>

        @foreach($byStudent as $studentId => $studentFees)
            @php($student = $studentFees->first()->student)
            @php($studentBalance = $studentFees->sum(fn ($fee) => $fee->balance()))
            <div class="mt-6 overflow-x-auto rounded-lg border border-[var(--border)]">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-[var(--surface-alt)] p-3 text-sm">
                    <span><strong>{{ $student?->fullName() }}</strong> <span class="text-[var(--ink-soft)]">({{ $student?->student_number }})</span></span>
                    <span>Owes <strong>{{ $money($studentBalance) }}</strong></span>
                </div>
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead><tr><th class="p-3">Fee</th><th class="p-3">Amount</th><th class="p-3">Paid</th><th class="p-3">Balance</th><th class="p-3">Due date</th>@if($paymentMode === 'installment')<th class="p-3">Pay</th>@endif</tr></thead>
                    <tbody>
                    @foreach($studentFees as $fee)
                        <tr class="border-t border-[var(--border)]">
                            <td class="p-3">{{ $fee->feeType?->name ?? 'School fee' }}@if($fee->description)<span class="block text-xs text-[var(--ink-soft)]">{{ $fee->description }}</span>@endif @if($fee->status === 'partial')<span class="block text-xs text-amber-700">Part paid</span>@elseif($fee->status === 'overdue')<span class="block text-xs text-red-700">Overdue</span>@endif</td>
                            <td class="p-3">{{ $money($fee->amount_due) }}</td>
                            <td class="p-3">{{ $money($fee->amount_paid) }}</td>
                            <td class="p-3"><strong>{{ $money($fee->balance()) }}</strong></td>
                            <td class="p-3">{{ $fee->due_date?->format('M j, Y') ?? '—' }}</td>
                            @if($paymentMode === 'installment')
                                <td class="p-3">
                                    @if($gatewayOptions->isEmpty())
                                        <span class="text-xs text-[var(--ink-soft)]">Online checkout is not set up. Contact the school office.</span>
                                    @else
                                        <form method="post" class="flex flex-wrap items-center gap-2" data-confirm="Continue to the secure checkout to pay this school fee?">
                                            @csrf
                                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                            <input type="text" inputmode="decimal" name="amount" value="{{ number_format($fee->balance(), 2, '.', '') }}" pattern="\d{1,9}(\.\d{1,2})?" aria-label="Amount to pay in naira" class="w-28 rounded-md border border-[var(--border)] p-2 text-sm">
                                            @foreach($gatewayOptions as $gateway)
                                                <button formaction="{{ route('payments.checkout', ['assignment' => $fee->id, 'gateway' => $gateway['driver']]) }}" class="rounded-md bg-[var(--brand-700)] px-3 py-2 text-xs font-semibold text-white">Pay with {{ $gateway['name'] }}</button>
                                            @endforeach
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if($paymentMode === 'full')
                    <div class="border-t border-[var(--border)] p-4">
                        @forelse($gatewayOptions as $gateway)
                            <form method="post" action="{{ route('payments.checkout', ['assignment' => $studentFees->first()->id, 'gateway' => $gateway['driver']]) }}" class="mr-2 inline-block" data-confirm="Pay all {{ $money($studentBalance) }} owing for this student?">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                <button class="mb-1 rounded-md bg-[var(--brand-700)] px-4 py-2 text-sm font-semibold text-white">Pay all {{ $money($studentBalance) }} with {{ $gateway['name'] }}</button>
                            </form>
                        @empty
                            <span class="text-xs text-[var(--ink-soft)]">Online checkout is not set up. Contact the school office.</span>
                        @endforelse
                    </div>
                @endif
            </div>
        @endforeach
    @endif

    @if($payments->isNotEmpty())
        <h3 class="mt-7 font-display text-xl font-semibold text-[var(--brand-800)]">Payments made</h3>
        <div class="mt-3 overflow-x-auto rounded-lg border border-[var(--border)]">
            <table class="w-full min-w-[650px] text-left text-sm">
                <thead class="bg-[var(--surface-alt)]"><tr>@if($kind === 'parent')<th class="p-3">Student</th>@endif<th class="p-3">Date</th><th class="p-3">Amount</th><th class="p-3">Method</th><th class="p-3">Status</th><th class="p-3">Receipt</th></tr></thead>
                <tbody>
                @foreach($payments as $payment)
                    <tr class="border-t border-[var(--border)]">
                        @if($kind === 'parent')<td class="p-3">{{ $payment->student?->fullName() }}</td>@endif
                        <td class="p-3">{{ $payment->created_at?->format('M j, Y') }}</td>
                        <td class="p-3">{{ $money($payment->amount) }}</td>
                        <td class="p-3">{{ ucfirst($payment->gateway) }}</td>
                        <td class="p-3">{{ $payment->status === 'successful' ? 'Verified' : ($payment->status === 'pending' ? 'Awaiting verification' : ucfirst($payment->status)) }}</td>
                        <td class="p-3">@if($payment->receipt && $payment->status === 'successful')<a class="font-semibold text-[var(--brand-700)] underline" href="{{ route('receipts.show', ['receipt' => $payment->receipt->receipt_number]) }}" target="_blank" rel="noopener">{{ $payment->receipt->receipt_number }} (print)</a>@else — @endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
