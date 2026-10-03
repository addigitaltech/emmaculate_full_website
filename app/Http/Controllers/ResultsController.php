<?php

namespace App\Http\Controllers;

use App\Domain\Results\Services\ResultCalculator;
use App\Domain\Results\Services\ResultReportBuilder;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\AssessmentConfig;
use App\Models\AuditLog;
use App\Models\GradeBand;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function entryForm(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('Super Admin') || $user->can('manage results');
        $teacherId = $user->teacherProfile()->value('id');
        abort_unless($isAdmin || ($teacherId && $user->hasRole('Teacher')), 403);
        $assignments = TeacherAssignment::query()->with(['schoolClass', 'arm', 'subject'])
            ->when(! $isAdmin, fn (Builder $query) => $query->where('teacher_id', $teacherId))
            ->orderBy('school_class_id')->get();
        $eligible = [];
        foreach ($assignments as $assignment) {
            $query = Student::query()->where('status', 'active')->where('school_class_id', $assignment->school_class_id);
            if ($assignment->arm_id) {
                $query->where('arm_id', $assignment->arm_id);
            }
            foreach ($query->get(['id', 'student_number', 'first_name', 'last_name', 'other_names', 'school_class_id', 'arm_id']) as $student) {
                $eligible[$student->id] = ['id' => $student->id, 'label' => $student->fullName().' · '.$student->student_number, 'class_id' => $student->school_class_id, 'arm_id' => $student->arm_id];
            }
        }
        $sessions = AcademicSession::query()->with('terms')->when(! $isAdmin, fn (Builder $q) => $q->where('is_active', true))->orderByDesc('starts_on')->get();
        return view('portal.score-entry', compact('assignments', 'eligible', 'sessions'));
    }

    public function store(Request $request, ResultCalculator $calculator): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:teacher_assignments,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'ca1_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ca2_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ca3_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'exam_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_offered' => ['nullable', 'boolean'],
            'teacher_remark' => ['nullable', 'string', 'max:255'],
        ]);
        $assignment = TeacherAssignment::query()->with('subject')->findOrFail($data['assignment_id']);
        $isAdmin = $user->hasRole('Super Admin') || $user->can('manage results');
        $teacherId = $user->teacherProfile()->value('id');
        abort_unless($isAdmin || ($user->hasRole('Teacher') && (int) $assignment->teacher_id === (int) $teacherId), 403);
        $student = Student::query()->where('status', 'active')->findOrFail($data['student_id']);
        abort_unless((int) $student->school_class_id === (int) $assignment->school_class_id, 422, 'The student is outside this teacher assignment.');
        abort_unless($assignment->arm_id === null || (int) $student->arm_id === (int) $assignment->arm_id, 422, 'The student is outside this teacher assignment.');
        $term = AcademicTerm::query()->where('academic_session_id', $data['academic_session_id'])->findOrFail($data['academic_term_id']);
        if (! $isAdmin) {
            abort_unless($term->is_current, 403, 'Teachers may enter scores only for the current academic term.');
        }
        $config = AssessmentConfig::query()->where('academic_session_id', $data['academic_session_id'])->where('academic_term_id', $term->id)->where('school_class_id', $assignment->school_class_id)->where('subject_id', $assignment->subject_id)->first();
        $settings = SchoolSettings::current();
        $maxima = $config?->maxima() ?? ['ca1' => $settings->ca1_max_score, 'ca2' => $settings->ca2_max_score, 'ca3' => $settings->ca3_max_score, 'exam' => $settings->exam_max_score];
        if (array_sum($maxima) !== 100) {
            throw ValidationException::withMessages(['assessment' => 'The assessment maxima must total 100 before results can be entered.']);
        }
        $bands = $this->gradeBands();
        $offered = $request->boolean('is_offered', true);
        $scores = $offered ? [
            'ca1' => $data['ca1_score'] ?? 0, 'ca2' => $data['ca2_score'] ?? 0,
            'ca3' => $data['ca3_score'] ?? 0, 'exam' => $data['exam_score'] ?? 0,
        ] : ['ca1' => 0, 'ca2' => 0, 'ca3' => 0, 'exam' => 0];
        try {
            $computed = $offered ? $calculator->calculate($scores, $maxima, $bands) : ['scores' => $scores, 'total_score' => 0, 'grade' => null, 'remark' => 'Not Offered'];
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['scores' => $exception->getMessage()]);
        }
        $result = DB::transaction(function () use ($student, $assignment, $data, $computed, $offered, $user): Result {
            $key = ['student_id' => $student->id, 'subject_id' => $assignment->subject_id, 'academic_session_id' => $data['academic_session_id'], 'academic_term_id' => $data['academic_term_id']];
            $result = Result::query()->lockForUpdate()->firstOrNew($key);
            if ($result->exists && $result->status === 'published') {
                abort(409, 'A published result cannot be overwritten. Use the approved correction workflow.');
            }
            $result->fill([
                'teacher_id' => $assignment->teacher_id, 'school_class_id' => $student->school_class_id, 'arm_id' => $student->arm_id,
                'ca1_score' => $computed['scores']['ca1'], 'ca2_score' => $computed['scores']['ca2'], 'ca3_score' => $computed['scores']['ca3'], 'exam_score' => $computed['scores']['exam'],
                'total_score' => $computed['total_score'], 'is_offered' => $offered, 'grade' => $computed['grade'], 'remark' => $computed['remark'],
                'teacher_remark' => $data['teacher_remark'] ?? null, 'status' => 'pending', 'published_by' => null, 'published_at' => null,
            ])->save();
            AuditLog::record($user, 'results.score_saved', $result, ['student_id' => $student->id, 'subject_id' => $assignment->subject_id, 'status' => 'pending']);
            return $result;
        });
        return redirect()->route('portal.dashboard')->with('success', 'Score saved for review. It is not visible to students or parents until published.');
    }

    public function publish(Request $request, Result $result, ResultCalculator $calculator): RedirectResponse
    {
        Gate::authorize('publish', $result);
        abort_unless(in_array($result->status, ['pending', 'draft'], true), 409, 'Only pending results can be published.');
        $config = AssessmentConfig::query()->where('academic_session_id', $result->academic_session_id)->where('academic_term_id', $result->academic_term_id)->where('school_class_id', $result->school_class_id)->where('subject_id', $result->subject_id)->first();
        $settings = SchoolSettings::current();
        $maxima = $config?->maxima() ?? ['ca1' => $settings->ca1_max_score, 'ca2' => $settings->ca2_max_score, 'ca3' => $settings->ca3_max_score, 'exam' => $settings->exam_max_score];
        if (array_sum($maxima) !== 100) {
            throw ValidationException::withMessages(['assessment' => 'Assessment maxima must total 100 before results can be published.']);
        }
        $computed = $result->is_offered ? $calculator->calculate(['ca1' => $result->ca1_score, 'ca2' => $result->ca2_score, 'ca3' => $result->ca3_score, 'exam' => $result->exam_score], $maxima, $this->gradeBands()) : ['total_score' => 0, 'grade' => null, 'remark' => 'Not Offered'];
        DB::transaction(function () use ($result, $computed, $request): void {
            $result->update(['total_score' => $computed['total_score'], 'grade' => $computed['grade'], 'remark' => $computed['remark'], 'status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
            AuditLog::record($request->user(), 'results.published', $result, ['student_id' => $result->student_id, 'subject_id' => $result->subject_id, 'academic_term_id' => $result->academic_term_id]);
        });
        return back()->with('success', 'Result published. It is now available to the linked student and parent portal accounts.');
    }

    public function report(Request $request, Result $result, ResultReportBuilder $builder)
    {
        Gate::authorize('view', $result);
        $data = $builder->build($result);
        if ($request->query('download') === 'pdf') {
            $pdf = Pdf::loadView('portal.report-card', $data + ['isPdf' => true])->setPaper('a4', 'landscape');
            return $pdf->download('emmaculate-result-'.$result->student_id.'-'.$result->academic_term_id.'.pdf');
        }
        return view('portal.report-card', $data + ['isPdf' => false]);
    }

    private function gradeBands(): array
    {
        $bands = GradeBand::query()->orderBy('min_score')->get()->map(fn (GradeBand $band) => ['min_score' => $band->min_score, 'max_score' => $band->max_score, 'grade' => $band->grade, 'remark' => $band->remark])->all();
        if (! $bands) {
            return ResultCalculator::DEFAULT_GRADE_BANDS;
        }
        $next = 0;
        foreach ($bands as $band) {
            if ($band['min_score'] !== $next || $band['max_score'] < $band['min_score']) {
                throw ValidationException::withMessages(['grade_bands' => 'The grade-band configuration has a gap or overlap.']);
            }
            $next = $band['max_score'] + 1;
        }
        if ($next !== 101) {
            throw ValidationException::withMessages(['grade_bands' => 'Grade bands must cover scores from 0 through 100.']);
        }
        return $bands;
    }
}
