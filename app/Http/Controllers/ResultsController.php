<?php

namespace App\Http\Controllers;

use App\Domain\Results\Services\ResultSheetService;
use App\Models\AcademicTerm;
use App\Models\Result;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Older single-result routes. They are kept so existing links keep working, but they no longer carry any
 * rules of their own: saving and publishing go through ResultSheetService and report cards go through the
 * one report page (ReportController), so every screen applies exactly the same score split, grading,
 * ranking and permissions.
 */
class ResultsController extends Controller
{
    public function store(Request $request, ResultSheetService $sheets): RedirectResponse
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
        $isAdmin = $sheets->isAdmin($user);
        abort_unless($isAdmin || (int) $assignment->teacher_id === (int) $sheets->teacherId($user), 403);

        $student = Student::query()->where('status', 'active')->findOrFail($data['student_id']);
        abort_unless((int) $student->school_class_id === (int) $assignment->school_class_id, 422, 'The student is outside this teacher assignment.');
        abort_unless($assignment->arm_id === null || (int) $student->arm_id === (int) $assignment->arm_id, 422, 'The student is outside this teacher assignment.');
        $term = AcademicTerm::query()->where('academic_session_id', $data['academic_session_id'])->findOrFail($data['academic_term_id']);

        $sheets->save(
            $user,
            $student,
            $assignment->subject,
            $term,
            [
                'ca1' => $data['ca1_score'] ?? 0,
                'ca2' => $data['ca2_score'] ?? 0,
                'ca3' => $data['ca3_score'] ?? 0,
                'exam' => $data['exam_score'] ?? 0,
            ],
            $request->boolean('is_offered', true),
            $data['teacher_remark'] ?? null,
        );

        return redirect()->route('staff.results')->with('success', 'Score saved for review. It is not visible to students or parents until published.');
    }

    public function publish(Request $request, Result $result, ResultSheetService $sheets): RedirectResponse
    {
        Gate::authorize('publish', $result);
        abort_unless(in_array($result->status, ['pending', 'draft'], true), 409, 'Only pending results can be published.');
        $sheets->publishResult($request->user(), $result);

        return back()->with('success', 'Result published. It is now available to the linked student and parent portal accounts.');
    }

    /** Old single-result report link: send it to the one report page, which enforces its own access rules. */
    public function report(Request $request, Result $result): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasRole('Teacher') && ! ($user->hasRole('Super Admin') || $user->can('manage results')), 403, 'Teachers can access results only through their assigned subject sheets.');
        Gate::authorize('view', $result);

        return redirect()->route('reports.show', ['student' => $result->student_id, 'term' => $result->academic_term_id] + $request->only('download'));
    }
}
