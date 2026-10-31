<?php

namespace App\Domain\Payments;

use App\Models\PaymentTransaction;
use App\Models\StudentFeeAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The numbers behind the payment history page and its printouts: every payment in a date range (or a whole session),
 * grouped and totalled, plus who still owes what. One builder feeds the screen, the print view, the PDF and the CSV,
 * so they can never disagree.
 */
final class PaymentReportBuilder
{
    /**
     * @param array{from?: ?string, to?: ?string, session_id?: ?int, class_id?: ?int, arm_id?: ?int, status?: ?string, q?: ?string} $filters
     * @return array{filters: array, payments: Collection, totals: array, byGateway: Collection, byClass: Collection, debts: Collection, debtTotals: array}
     */
    public function build(array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
        $status = in_array($filters['status'] ?? 'successful', ['successful', 'pending', 'failed', 'refunded', 'all'], true) ? ($filters['status'] ?? 'successful') : 'successful';
        $sessionId = $filters['session_id'] ?? null;
        $classId = $filters['class_id'] ?? null;
        $armId = $filters['arm_id'] ?? null;
        $term = trim((string) ($filters['q'] ?? ''));

        $payments = PaymentTransaction::query()
            ->with(['student.schoolClass', 'student.arm', 'initiator', 'receipt', 'allocations.feeAssignment.feeType', 'feeAssignment.feeType'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->when($sessionId, fn ($query) => $query->where(fn ($inner) => $inner
                ->whereHas('allocations.feeAssignment', fn ($fee) => $fee->where('academic_session_id', $sessionId))
                ->orWhereHas('feeAssignment', fn ($fee) => $fee->where('academic_session_id', $sessionId))))
            ->when($classId, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $classId)))
            ->when($armId, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('arm_id', $armId)))
            ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', '%'.addcslashes($term, '%_\\').'%')
                ->orWhereHas('student', fn ($student) => $student->where('student_number', 'like', '%'.addcslashes($term, '%_\\').'%')
                    ->orWhere('first_name', 'like', '%'.addcslashes($term, '%_\\').'%')->orWhere('last_name', 'like', '%'.addcslashes($term, '%_\\').'%'))))
            ->orderBy('created_at')
            ->limit(5000)
            ->get();

        $successful = $payments->where('status', 'successful');
        $totals = [
            'count' => $payments->count(),
            'paid_count' => $successful->count(),
            'paid_total' => round((float) $successful->sum('amount'), 2),
        ];
        $byGateway = $successful->groupBy('gateway')->map(fn (Collection $rows, $gateway) => ['gateway' => ucfirst((string) $gateway), 'count' => $rows->count(), 'total' => round((float) $rows->sum('amount'), 2)])->values();
        $byClass = $successful->groupBy(fn ($payment) => $payment->student?->schoolClass?->name ?? 'No class')
            ->map(fn (Collection $rows, $class) => ['class' => $class, 'count' => $rows->count(), 'total' => round((float) $rows->sum('amount'), 2)])->sortBy('class')->values();

        [$debts, $debtTotals] = $this->debts($sessionId, $classId, $armId, $term);

        return ['filters' => $filters + ['status' => $status], 'payments' => $payments, 'totals' => $totals, 'byGateway' => $byGateway, 'byClass' => $byClass, 'debts' => $debts, 'debtTotals' => $debtTotals];
    }

    /** Students who still owe money, with what they were charged, what they paid and what is left. */
    private function debts(?int $sessionId, ?int $classId, ?int $armId, string $term): array
    {
        $fees = StudentFeeAssignment::query()
            ->with(['student.schoolClass', 'student.arm', 'feeType'])
            ->whereIn('status', FeePaymentPlanner::PAYABLE)
            ->when($sessionId, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->when($classId, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $classId)))
            ->when($armId, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('arm_id', $armId)))
            ->when($term !== '', fn ($query) => $query->whereHas('student', fn ($student) => $student->where('student_number', 'like', '%'.addcslashes($term, '%_\\').'%')
                ->orWhere('first_name', 'like', '%'.addcslashes($term, '%_\\').'%')->orWhere('last_name', 'like', '%'.addcslashes($term, '%_\\').'%')))
            ->get();

        $debts = $fees->groupBy('student_id')->map(function (Collection $rows) {
            $student = $rows->first()->student;
            $due = round((float) $rows->sum('amount_due'), 2);
            $paid = round((float) $rows->sum('amount_paid'), 2);

            return [
                'student' => $student,
                'class' => trim(($student?->schoolClass?->name ?? '').($student?->arm ? ' '.$student->arm->name : '')),
                'fees' => $rows->map(fn ($fee) => ($fee->feeType?->name ?? 'Fee').' ('.number_format($fee->balance(), 2).')')->implode('; '),
                'due' => $due,
                'paid' => $paid,
                'balance' => max(0.0, round($due - $paid, 2)),
            ];
        })->filter(fn ($row) => $row['balance'] > 0)->sortByDesc('balance')->values();

        return [$debts, ['students' => $debts->count(), 'due' => round((float) $debts->sum('due'), 2), 'paid' => round((float) $debts->sum('paid'), 2), 'balance' => round((float) $debts->sum('balance'), 2)]];
    }
}
