<?php

namespace App\Http\Controllers;

use App\Domain\Payments\PaymentReportBuilder;
use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Printable payment history for a date range or a whole session (screen, PDF or spreadsheet). Finance staff only. */
class FinanceReportController extends Controller
{
    public function show(Request $request, PaymentReportBuilder $builder)
    {
        // Every filter is checked before it is used: dates, numbers and a fixed list of statuses only.
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'arm_id' => ['nullable', 'integer', 'exists:arms,id'],
            'status' => ['nullable', 'in:successful,pending,failed,refunded,all'],
            'q' => ['nullable', 'string', 'max:80'],
            'download' => ['nullable', 'in:pdf,csv'],
        ]);
        $report = $builder->build($filters);

        if (($filters['download'] ?? null) === 'csv') {
            return $this->csv($report);
        }

        $settings = SchoolSettings::current();
        $logo = $settings->logo_path && Storage::disk('public')->exists($settings->logo_path) ? Storage::disk('public')->path($settings->logo_path) : null;
        $data = $report + [
            'settings' => $settings,
            'isPdf' => ($filters['download'] ?? null) === 'pdf',
            'logoPath' => $logo,
            'session' => ! empty($filters['session_id']) ? AcademicSession::query()->find($filters['session_id']) : null,
            'class' => ! empty($filters['class_id']) ? SchoolClass::query()->find($filters['class_id']) : null,
            'arm' => ! empty($filters['arm_id']) ? Arm::query()->find($filters['arm_id']) : null,
            'pdfUrl' => route('finance.report', array_filter($filters, fn ($value) => $value !== null && $value !== '') + ['download' => 'pdf']),
        ];

        if ($data['isPdf']) {
            return Pdf::loadView('finance.report', $data)->setPaper('a4', 'landscape')->download('payment-history-'.now()->format('Ymd').'.pdf');
        }

        return view('finance.report', $data);
    }

    private function csv(array $report)
    {
        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Receipt', 'Reference', 'Student ID', 'Student', 'Class', 'Paid by', 'Method', 'Status', 'Amount (NGN)', 'Fees covered']);
            foreach ($report['payments'] as $payment) {
                $student = $payment->student;
                $covered = $payment->allocations->isNotEmpty()
                    ? $payment->allocations->map(fn ($allocation) => ($allocation->feeAssignment?->feeType?->name ?? 'Fee').' '.number_format((float) $allocation->amount, 2))->implode('; ')
                    : ($payment->feeAssignment?->feeType?->name ?? '');
                fputcsv($out, array_map([self::class, 'safeCell'], [
                    $payment->created_at?->format('Y-m-d H:i'), $payment->receipt?->receipt_number, $payment->reference, $student?->student_number,
                    $student?->fullName(), trim(($student?->schoolClass?->name ?? '').' '.($student?->arm?->name ?? '')), $payment->initiator?->name,
                    ucfirst($payment->gateway), $payment->status, number_format((float) $payment->amount, 2, '.', ''), $covered,
                ]));
            }
            fputcsv($out, []);
            fputcsv($out, ['Still owing']);
            fputcsv($out, ['Student ID', 'Student', 'Class', 'Charged (NGN)', 'Paid (NGN)', 'Balance (NGN)', 'Unpaid fees']);
            foreach ($report['debts'] as $row) {
                fputcsv($out, array_map([self::class, 'safeCell'], [$row['student']?->student_number, $row['student']?->fullName(), $row['class'], $row['due'], $row['paid'], $row['balance'], $row['fees']]));
            }
            fclose($out);
        }, 'payment-history-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** A spreadsheet runs anything that starts with = + - @ as a formula: prefix such cells so names and notes can never execute. */
    public static function safeCell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
