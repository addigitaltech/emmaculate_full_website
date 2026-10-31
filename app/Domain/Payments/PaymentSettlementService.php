<?php

namespace App\Domain\Payments;

use App\Models\AuditLog;
use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use App\Models\StudentFeeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PaymentSettlementService
{
    public function __construct(private readonly PaymentStatusTransition $transitions) {}

    public function settleVerified(PaymentTransaction $transaction, ?string $providerReference = null, ?User $actor = null): bool
    {
        $newlySettledId = null;

        $result = DB::transaction(function () use ($transaction, $providerReference, $actor, &$newlySettledId): bool {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status === 'successful') return true;
            if (! in_array($locked->status, ['pending', 'processing'], true)) return false;
            $this->transitions->transition($locked, 'successful', providerVerified: true);
            if ($providerReference !== null && $providerReference !== '') {
                $locked->provider_reference = $providerReference;
                $locked->save();
            }
            $this->applyToFees($locked);
            PaymentReceipt::query()->firstOrCreate(
                ['payment_transaction_id' => $locked->id],
                ['receipt_number' => 'EAR-'.now()->format('ym').'-'.Str::upper(Str::random(10)), 'issued_at' => now()],
            );
            AuditLog::record($actor, 'payments.verified_and_settled', $locked, [
                'gateway' => $locked->gateway,
                'amount' => (string) $locked->amount,
                'currency' => $locked->currency,
                'fee_assignment_id' => $locked->fee_assignment_id,
                'fees_covered' => $locked->allocations()->count(),
            ]);
            $newlySettledId = $locked->id;

            return true;
        });

        // Tell the school and the payer (email, SMS). A failure here must never undo a verified payment.
        if ($newlySettledId !== null) {
            try {
                app(PaymentNotifier::class)->paymentSettled($newlySettledId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }

    /** Put a verified payment against the fees it was made for (a whole fee, part of a fee, or several fees). */
    private function applyToFees(PaymentTransaction $transaction): void
    {
        $allocations = $transaction->allocations()->get();

        if ($allocations->isEmpty()) {
            // Older single-fee payments carry no allocation rows: they paid the whole fee.
            $fee = $transaction->fee_assignment_id ? StudentFeeAssignment::query()->lockForUpdate()->find($transaction->fee_assignment_id) : null;
            if ($fee) {
                $fee->forceFill(['amount_paid' => $fee->amount_due, 'status' => 'paid'])->save();
            }

            return;
        }

        foreach ($allocations as $allocation) {
            $fee = StudentFeeAssignment::query()->lockForUpdate()->find($allocation->fee_assignment_id);
            if (! $fee) {
                continue;
            }
            $paid = min((float) $fee->amount_due, round((float) $fee->amount_paid + (float) $allocation->amount, 2));
            $fee->forceFill(['amount_paid' => $paid, 'status' => $paid >= (float) $fee->amount_due ? 'paid' : 'partial'])->save();
        }
    }

    /** Take a refunded payment back off the fees it paid. */
    private function reverseOnFees(PaymentTransaction $transaction): void
    {
        $allocations = $transaction->allocations()->get();

        if ($allocations->isEmpty()) {
            $fee = $transaction->feeAssignment;
            if ($fee && $fee->status === 'paid') {
                $nextStatus = $fee->due_date && $fee->due_date->lt(today()) ? 'overdue' : 'unpaid';
                $fee->forceFill(['amount_paid' => 0, 'status' => $nextStatus])->save();
            }

            return;
        }

        foreach ($allocations as $allocation) {
            $fee = StudentFeeAssignment::query()->lockForUpdate()->find($allocation->fee_assignment_id);
            if (! $fee) {
                continue;
            }
            $paid = max(0.0, round((float) $fee->amount_paid - (float) $allocation->amount, 2));
            $status = $paid > 0 ? 'partial' : ($fee->due_date && $fee->due_date->lt(today()) ? 'overdue' : 'unpaid');
            $fee->forceFill(['amount_paid' => $paid, 'status' => $status])->save();
        }
    }

    /** Only call after provider-authenticated completion; never from a browser return URL. */
    public function markRefunded(PaymentTransaction $transaction, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($transaction, $actor): bool {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status === 'refunded') return true;
            if ($locked->status !== 'successful') return false;
            $this->transitions->transition($locked, 'refunded', providerVerified: true);
            $this->reverseOnFees($locked);
            AuditLog::record($actor, 'payments.refund_completed', $locked, [
                'gateway' => $locked->gateway,
                'fee_assignment_id' => $locked->fee_assignment_id,
                'refund_reference_hash' => $locked->refund_reference ? hash('sha256', $locked->refund_reference) : null,
            ]);
            return true;
        });
    }
}
