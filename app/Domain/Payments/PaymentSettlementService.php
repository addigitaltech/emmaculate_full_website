<?php

namespace App\Domain\Payments;

use App\Models\AuditLog;
use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PaymentSettlementService
{
    public function __construct(private readonly PaymentStatusTransition $transitions) {}

    public function settleVerified(PaymentTransaction $transaction, ?string $providerReference = null, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($transaction, $providerReference, $actor): bool {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status === 'successful') return true;
            if (! in_array($locked->status, ['pending', 'processing'], true)) return false;
            $this->transitions->transition($locked, 'successful', providerVerified: true);
            if ($providerReference !== null && $providerReference !== '') {
                $locked->provider_reference = $providerReference;
                $locked->save();
            }
            if ($locked->feeAssignment) $locked->feeAssignment->update(['status' => 'paid']);
            PaymentReceipt::query()->firstOrCreate(
                ['payment_transaction_id' => $locked->id],
                ['receipt_number' => 'EAR-'.now()->format('ym').'-'.Str::upper(Str::random(10)), 'issued_at' => now()],
            );
            AuditLog::record($actor, 'payments.verified_and_settled', $locked, [
                'gateway' => $locked->gateway,
                'amount' => (string) $locked->amount,
                'currency' => $locked->currency,
                'fee_assignment_id' => $locked->fee_assignment_id,
            ]);
            return true;
        });
    }

    /** Only call after provider-authenticated completion; never from a browser return URL. */
    public function markRefunded(PaymentTransaction $transaction, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($transaction, $actor): bool {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($locked->status === 'refunded') return true;
            if ($locked->status !== 'successful') return false;
            $this->transitions->transition($locked, 'refunded', providerVerified: true);
            $fee = $locked->feeAssignment;
            if ($fee && $fee->status === 'paid') {
                $nextStatus = $fee->due_date && $fee->due_date->lt(today()) ? 'overdue' : 'unpaid';
                $fee->update(['status' => $nextStatus]);
            }
            AuditLog::record($actor, 'payments.refund_completed', $locked, [
                'gateway' => $locked->gateway,
                'fee_assignment_id' => $locked->fee_assignment_id,
                'refund_reference_hash' => $locked->refund_reference ? hash('sha256', $locked->refund_reference) : null,
            ]);
            return true;
        });
    }
}
