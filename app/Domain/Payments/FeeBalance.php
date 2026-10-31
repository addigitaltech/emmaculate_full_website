<?php

namespace App\Domain\Payments;

use App\Models\StudentFeeAssignment;

/** What a student still owes, worked out from the fee records. */
final class FeeBalance
{
    /** @return array{due: float, paid: float, balance: float, items: int} */
    public static function forStudent(int $studentId): array
    {
        $fees = StudentFeeAssignment::query()->where('student_id', $studentId)->whereIn('status', FeePaymentPlanner::PAYABLE)->get(['amount_due', 'amount_paid']);
        $due = round((float) $fees->sum('amount_due'), 2);
        $paid = round((float) $fees->sum('amount_paid'), 2);

        return ['due' => $due, 'paid' => $paid, 'balance' => max(0.0, round($due - $paid, 2)), 'items' => $fees->count()];
    }
}
