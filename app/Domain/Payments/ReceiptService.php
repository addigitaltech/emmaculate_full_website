<?php

namespace App\Domain\Payments;

use App\Models\PaymentReceipt;
use App\Models\SchoolSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/** Builds the printable receipt (screen or PDF) for a verified payment. */
final class ReceiptService
{
    public function data(PaymentReceipt $receipt, bool $isPdf = false): array
    {
        $receipt->loadMissing(['transaction.student.schoolClass', 'transaction.student.arm', 'transaction.initiator', 'transaction.allocations.feeAssignment.feeType', 'transaction.feeAssignment.feeType']);
        $transaction = $receipt->transaction;
        $settings = SchoolSettings::current();
        $items = $transaction->allocations->isNotEmpty()
            ? $transaction->allocations->map(fn ($allocation) => [
                'fee' => $allocation->feeAssignment?->feeType?->name ?? 'School fee',
                'note' => $allocation->feeAssignment?->description,
                'amount' => (float) $allocation->amount,
                'fee_total' => (float) ($allocation->feeAssignment?->amount_due ?? 0),
            ])->all()
            : [[
                'fee' => $transaction->feeAssignment?->feeType?->name ?? 'School fee',
                'note' => $transaction->feeAssignment?->description,
                'amount' => (float) $transaction->amount,
                'fee_total' => (float) ($transaction->feeAssignment?->amount_due ?? $transaction->amount),
            ]];
        $logo = $settings->logo_path && Storage::disk('public')->exists($settings->logo_path) ? $settings->logo_path : null;

        return [
            'receipt' => $receipt,
            'transaction' => $transaction,
            'student' => $transaction->student,
            'items' => $items,
            'balance' => FeeBalance::forStudent((int) $transaction->student_id),
            'settings' => $settings,
            'isPdf' => $isPdf,
            'logoPath' => $logo ? Storage::disk('public')->path($logo) : null,
            'logoUrl' => $logo ? \App\Support\MediaUrl::for($logo) : null,
        ];
    }

    public function pdf(PaymentReceipt $receipt): string
    {
        return Pdf::loadView('portal.receipt', $this->data($receipt, true))->setPaper('a4', 'portrait')->output();
    }
}
