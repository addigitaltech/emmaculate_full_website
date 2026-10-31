<?php

namespace App\Domain\Payments;

use App\Models\SchoolSettings;
use App\Models\StudentFeeAssignment;
use Illuminate\Validation\ValidationException;

/**
 * Works out, on the server, exactly what a payment will cover. Nothing the browser sends is trusted: the fee, its
 * balance, the school's payment mode and the minimum installment all come from the database, and the amount a parent
 * types is only accepted if it is a clean naira amount between the minimum and the balance.
 */
final class FeePaymentPlanner
{
    public const PAYABLE = ['unpaid', 'overdue', 'partial'];

    /** Largest amount one online payment may carry (naira). */
    public const MAX_PAYMENT = 100_000_000;

    /**
     * @return array{mode: string, total: string, allocations: array<int, array{fee: StudentFeeAssignment, amount: string}>}
     */
    public function plan(StudentFeeAssignment $assignment, mixed $requestedAmount = null): array
    {
        $settings = SchoolSettings::current();
        $mode = $settings->payment_mode === 'full' ? 'full' : 'installment';

        if (strtoupper((string) $assignment->currency) !== 'NGN') {
            throw ValidationException::withMessages(['payment' => 'This fee cannot be paid online.']);
        }

        if ($mode === 'full') {
            // Full payment: every unpaid fee of the student is added together and paid in one go.
            $fees = StudentFeeAssignment::query()
                ->where('student_id', $assignment->student_id)
                ->whereIn('status', self::PAYABLE)
                ->where('currency', 'NGN')
                ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('id')
                ->get()
                ->filter(fn (StudentFeeAssignment $fee) => $fee->balance() > 0)
                ->values();
            if ($fees->isEmpty()) {
                throw ValidationException::withMessages(['payment' => 'There is nothing to pay for this student.']);
            }
            $kobo = 0;
            $allocations = [];
            foreach ($fees as $fee) {
                $feeKobo = $this->toKobo($fee->balance());
                $kobo += $feeKobo;
                $allocations[] = ['fee' => $fee, 'amount' => $this->fromKobo($feeKobo)];
            }

            return $this->finish('full', $kobo, $allocations);
        }

        // Installment: pay all or part of this one fee.
        if (! in_array($assignment->status, self::PAYABLE, true) || $assignment->balance() <= 0) {
            throw ValidationException::withMessages(['payment' => 'This fee is not currently payable.']);
        }
        $balanceKobo = $this->toKobo($assignment->balance());
        $amountKobo = $balanceKobo;
        if ($requestedAmount !== null && $requestedAmount !== '') {
            $amountKobo = $this->parseAmount($requestedAmount);
            if ($amountKobo > $balanceKobo) {
                throw ValidationException::withMessages(['amount' => 'The amount is more than the balance of '.$this->fromKobo($balanceKobo).'.']);
            }
            $minKobo = min($this->toKobo((float) $settings->installment_min_amount), $balanceKobo);
            if ($amountKobo < $minKobo) {
                throw ValidationException::withMessages(['amount' => 'The smallest payment allowed is '.$this->fromKobo($minKobo).'.']);
            }
        }

        return $this->finish('installment', $amountKobo, [['fee' => $assignment, 'amount' => $this->fromKobo($amountKobo)]]);
    }

    /** Only a plain naira amount (digits and at most two decimals) is accepted. */
    public function parseAmount(mixed $value): int
    {
        $text = is_string($value) ? trim($value) : (is_int($value) || is_float($value) ? (string) $value : '');
        if (! preg_match('/^\d{1,9}(\.\d{1,2})?$/', $text)) {
            throw ValidationException::withMessages(['amount' => 'Enter the amount in naira, for example 15000 or 15000.50.']);
        }
        $kobo = $this->toKobo((float) $text);
        if ($kobo <= 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be more than zero.']);
        }

        return $kobo;
    }

    private function finish(string $mode, int $kobo, array $allocations): array
    {
        if ($kobo <= 0 || $kobo > self::MAX_PAYMENT * 100) {
            throw ValidationException::withMessages(['payment' => 'This payment amount is not allowed.']);
        }

        return ['mode' => $mode, 'total' => $this->fromKobo($kobo), 'allocations' => $allocations];
    }

    private function toKobo(float $naira): int
    {
        return (int) round($naira * 100);
    }

    private function fromKobo(int $kobo): string
    {
        return number_format($kobo / 100, 2, '.', '');
    }
}
