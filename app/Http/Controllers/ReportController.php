<?php

namespace App\Http\Controllers;

use App\Domain\Results\Services\ResultSheetService;
use App\Domain\Results\Services\TermReportBuilder;
use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Models\StudentFeeAssignment;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function show(Request $request, Student $student, AcademicTerm $term, TermReportBuilder $builder, ResultSheetService $sheets)
    {
        $user = $request->user();
        $staffView = $sheets->isAdmin($user) || $sheets->teachesClass($user, $student->school_class_id, $student->arm_id);

        if (! $staffView) {
            if ($user->hasRole('Student')) {
                abort_unless((int) $user->studentProfile()->value('id') === (int) $student->id, 403);
            } elseif ($user->hasRole('Parent')) {
                $parent = $user->parentProfile()->first();
                abort_unless($parent && $parent->students()->whereKey($student->id)->exists(), 403);
            } else {
                abort(403);
            }
            // Families only ever see published results.
            abort_unless(Result::published()->where('student_id', $student->id)->where('academic_term_id', $term->id)->exists(), 404);
            if (SchoolSettings::current()->payment_gate_results
                && StudentFeeAssignment::query()->where('student_id', $student->id)->whereIn('status', ['unpaid', 'overdue'])->exists()) {
                abort(403, 'This report becomes available once outstanding school fees have been settled.');
            }
        }

        $data = $builder->build($student, $term, $staffView);
        if ($request->query('download') === 'pdf') {
            AuditLog::record($user, 'results.report_downloaded', $student, ['term_id' => $term->id]);
            $pdf = Pdf::loadView('portal.report', $data + ['isPdf' => true])->setPaper('a4', 'landscape');

            return $pdf->download('report-'.Str::slug($student->fullName()).'-'.$term->id.'.pdf');
        }

        return view('portal.report', $data + ['isPdf' => false]);
    }
}
