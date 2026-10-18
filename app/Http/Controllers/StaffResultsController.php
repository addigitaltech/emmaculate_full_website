<?php

namespace App\Http\Controllers;

use App\Domain\Results\Services\ResultCalculator;
use App\Domain\Results\Services\ResultSheetService;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\AffectiveRating;
use App\Models\AffectiveTrait;
use App\Models\ClassTeacher;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\TermRemark;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Staff area for results: pick a class, open a student's full sheet or a subject's class sheet,
 * publish, correct and search the archive. Authorisation is enforced here and in ResultSheetService,
 * never by hiding links.
 */
class StaffResultsController extends Controller
{
    public function __construct(private readonly ResultSheetService $sheets)
    {
    }

    /** Class / arm / term picker plus the teacher's own score sheets. */
    public function home(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $this->sheets->isAdmin($user);
        $term = $this->resolveTerm($request);
        $assignments = collect();
        if (! $isAdmin) {
            $assignments = TeacherAssignment::query()
                ->where('teacher_id', $this->sheets->teacherId($user) ?? 0)
                ->with(['schoolClass', 'arm', 'subject'])
                ->get()
                ->sortBy(fn (TeacherAssignment $a) => ($a->schoolClass?->name ?? '').($a->subject?->name ?? ''))
                ->values();
        }
        $classes = SchoolClass::query()->where('is_active', true)->with('arms')->orderBy('name')->get();
        if (! $isAdmin) {
            $allowed = $assignments->pluck('school_class_id')
                ->merge(ClassTeacher::query()->where('teacher_id', $this->sheets->teacherId($user) ?? 0)->pluck('school_class_id'))
                ->unique();
            $classes = $classes->whereIn('id', $allowed)->values();
        }

        // Administrators see whose published report is currently held back because of unpaid fees. Calculation,
        // grading and positions are unaffected: the fee gate only hides the report from the student and parent.
        $feeGateOn = false;
        $withheld = collect();
        if ($isAdmin && $term) {
            $feeGateOn = (bool) SchoolSettings::current()->payment_gate_results;
            if ($feeGateOn) {
                $owing = StudentFeeAssignment::query()->whereIn('status', ['unpaid', 'overdue'])->get(['student_id', 'amount_due', 'currency'])->groupBy('student_id');
                $withheld = Student::query()
                    ->with(['schoolClass', 'arm'])
                    ->whereIn('id', $owing->keys())
                    ->whereIn('id', Result::published()->where('academic_term_id', $term->id)->select('student_id'))
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get()
                    ->map(fn (Student $student): array => [
                        'student' => $student,
                        'items' => $owing[$student->id]->count(),
                        'amount' => $owing[$student->id]->sum('amount_due'),
                        'currency' => $owing[$student->id]->first()->currency ?: 'NGN',
                    ]);
            }
        }

        return view('portal.staff.results-home', [
            'isAdmin' => $isAdmin,
            'term' => $term,
            'sessions' => AcademicSession::query()->with('terms')->orderByDesc('starts_on')->orderByDesc('id')->get(),
            'classes' => $classes,
            'assignments' => $assignments,
            'canPublish' => $user->can('publish results'),
            'feeGateOn' => $feeGateOn,
            'withheld' => $withheld,
        ]);
    }

    /** A class-wide result matrix; teachers only see their assigned subjects in their assigned class/arm. */
    public function broadsheet(Request $request, ResultCalculator $calculator)
    {
        $data = $request->validate([
            'session' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'term' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'class' => ['nullable', 'integer', 'exists:school_classes,id'],
            'arm' => ['nullable', 'integer', 'exists:arms,id'],
        ]);
        $user = $request->user();
        $isAdmin = $this->sheets->isAdmin($user);
        $sessionId = ! empty($data['session']) ? (int) $data['session'] : null;
        $term = null;
        if (! empty($data['term'])) {
            $term = AcademicTerm::query()->with('session')->findOrFail((int) $data['term']);
            abort_if($sessionId && $sessionId !== (int) $term->academic_session_id, 422, 'The selected term does not belong to the selected session.');
            $sessionId = (int) $term->academic_session_id;
        }

        $classId = ! empty($data['class']) ? (int) $data['class'] : null;
        $armId = ! empty($data['arm']) ? (int) $data['arm'] : null;
        abort_if($armId && ! $classId, 422, 'Select a class before selecting an arm.');
        $class = $classId ? SchoolClass::query()->with('arms')->findOrFail($classId) : null;
        if ($class && $armId) {
            abort_unless($class->arms()->whereKey($armId)->exists(), 422);
        }
        if ($class) {
            abort_unless($this->sheets->teachesClass($user, $classId, $armId), 403);
        }

        $teacherId = $isAdmin ? null : $this->sheets->teacherId($user);
        $teacherAssignments = $isAdmin
            ? collect()
            : TeacherAssignment::query()->where('teacher_id', $teacherId ?? 0)->get();
        $classes = SchoolClass::query()->with('arms')->orderBy('name')->get();
        if (! $isAdmin) {
            $classes = $classes->whereIn('id', $teacherAssignments->pluck('school_class_id')->unique())->values();
        }

        $rows = collect();
        $subjects = collect();
        $ready = (bool) ($term && $class);
        if ($ready) {
            $assignedSubjectIds = null;
            if (! $isAdmin) {
                $assignedSubjectIds = TeacherAssignment::query()
                    ->where('teacher_id', $teacherId ?? 0)
                    ->where('school_class_id', $class->id)
                    ->where(function ($query) use ($armId): void {
                        $query->whereNull('arm_id');
                        if ($armId) {
                            $query->orWhere('arm_id', $armId);
                        }
                    })
                    ->pluck('subject_id')->unique()->values();
            }

            $results = Result::query()
                ->where('academic_session_id', $term->academic_session_id)
                ->where('academic_term_id', $term->id)
                ->where('school_class_id', $class->id)
                ->when($armId, fn ($query) => $query->where('arm_id', $armId))
                ->when($assignedSubjectIds !== null, fn ($query) => $query->whereIn('subject_id', $assignedSubjectIds))
                ->get();

            $rosterIds = Student::query()
                ->where('status', 'active')
                ->where('school_class_id', $class->id)
                ->when($armId, fn ($query) => $query->where('arm_id', $armId))
                ->pluck('id');
            $studentIds = $rosterIds->merge($results->pluck('student_id'))->unique()->values();
            $students = Student::withTrashed()->whereIn('id', $studentIds)->orderBy('last_name')->orderBy('first_name')->get();

            $subjectIds = Subject::query()
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('school_class_id')->orWhere('school_class_id', $class->id))
                ->when($armId, fn ($query) => $query->where(fn ($armQuery) => $armQuery->whereNull('arm_id')->orWhere('arm_id', $armId)))
                ->pluck('id')
                ->merge($results->pluck('subject_id'))
                ->unique()->values();
            if ($assignedSubjectIds !== null) {
                $subjectIds = $subjectIds->intersect($assignedSubjectIds)->values();
            }
            $subjects = Subject::query()->whereIn('id', $subjectIds)->orderBy('name')->get();

            $resultMap = $results->keyBy(fn (Result $result): string => $result->student_id.':'.$result->subject_id);
            $subjectStats = $subjects->mapWithKeys(function (Subject $subject) use ($results): array {
                $scores = $results->where('subject_id', $subject->id)
                    ->filter(fn (Result $result): bool => $result->is_offered)
                    ->pluck('total_score')->map(fn ($score): float => (float) $score)->values();

                return [$subject->id => [
                    'scores' => $scores,
                    'average' => $scores->isNotEmpty() ? round($scores->avg(), 1) : null,
                    'highest' => $scores->isNotEmpty() ? (float) $scores->max() : null,
                    'lowest' => $scores->isNotEmpty() ? (float) $scores->min() : null,
                    'population' => $scores->count(),
                ]];
            });

            $rows = $students->map(function (Student $student) use ($subjects, $resultMap, $subjectStats): array {
                $subjectRows = $subjects->map(function (Subject $subject) use ($student, $resultMap, $subjectStats): array {
                    $result = $resultMap->get($student->id.':'.$subject->id);
                    $stats = $subjectStats->get($subject->id);
                    $score = $result && $result->is_offered ? (float) $result->total_score : null;

                    return [
                        'subject' => $subject,
                        'result' => $result,
                        'ca1' => $result && $result->is_offered ? (float) $result->ca1_score : null,
                        'ca2' => $result && $result->is_offered ? (float) $result->ca2_score : null,
                        'ca3' => $result && $result->is_offered ? (float) $result->ca3_score : null,
                        'exam' => $result && $result->is_offered ? (float) $result->exam_score : null,
                        'total' => $score,
                        'grade' => $result?->grade,
                        'not_offered' => $result && ! $result->is_offered,
                        'status' => $result?->status,
                        'average' => $stats['average'],
                        'highest' => $stats['highest'],
                        'lowest' => $stats['lowest'],
                        'population' => $stats['population'],
                        'position' => $score === null ? null : 1 + $stats['scores']->filter(fn ($other): bool => (float) $other > $score)->count(),
                    ];
                });
                $offeredRows = $subjectRows->filter(fn (array $row): bool => $row['total'] !== null);
                $total = round((float) $offeredRows->sum('total'), 2);

                return [
                    'student' => $student,
                    'subjects' => $subjectRows,
                    'total' => $total,
                    'average' => $offeredRows->isNotEmpty() ? round($total / $offeredRows->count(), 2) : null,
                    'offered' => $offeredRows->count(),
                    'position' => null,
                ];
            });

            $overallScores = $rows->filter(fn (array $row): bool => $row['average'] !== null)->pluck('average')->all();
            $rows = $rows->map(function (array $row) use ($isAdmin, $calculator, $overallScores): array {
                if ($isAdmin && $row['average'] !== null) {
                    $row['position'] = $calculator->competitionRank((float) $row['average'], $overallScores);
                }

                return $row;
            })->sort(function (array $left, array $right): int {
                $byAverage = ($right['average'] ?? -1) <=> ($left['average'] ?? -1);
                return $byAverage ?: strcasecmp((string) $left['student']->last_name, (string) $right['student']->last_name);
            })->values();
        }

        $viewData = [
            'isAdmin' => $isAdmin,
            'ready' => $ready,
            'sessions' => AcademicSession::query()->with('terms')->orderByDesc('starts_on')->orderByDesc('id')->get(),
            'classes' => $classes,
            'term' => $term,
            'sessionId' => $sessionId,
            'class' => $class,
            'armId' => $armId,
            'subjects' => $subjects,
            'rows' => $rows,
            'settings' => \App\Models\SchoolSettings::current(),
        ];

        if ($ready && $request->query('download') === 'pdf') {
            return Pdf::loadView('portal.staff.broadsheet-pdf', $viewData)
                ->setPaper('a3', 'landscape')
                ->download('class-broadsheet-'.$term->id.'.pdf');
        }

        return view('portal.staff.broadsheet', $viewData + ['isPdf' => false]);
    }

    /** Students of one class (and arm) with Enter / Print actions, as in the school's reference system. */
    public function students(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'class' => ['required', 'integer', 'exists:school_classes,id'],
            'arm' => ['nullable', 'integer', 'exists:arms,id'],
            'term' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $user = $request->user();
        $classId = (int) $data['class'];
        $armId = ! empty($data['arm']) ? (int) $data['arm'] : null;
        abort_unless($this->sheets->teachesClass($user, $classId, $armId), 403);
        $term = $this->resolveTerm($request);
        if (! $term) {
            return redirect()->route('staff.results')->withErrors(['term' => 'Create an academic session and term first (Admin > Results setup).']);
        }
        $class = SchoolClass::query()->findOrFail($classId);
        if ($armId) {
            abort_unless($class->arms()->whereKey($armId)->exists(), 422);
        }
        $needle = Str::lower(trim((string) ($data['q'] ?? '')));
        $students = Student::query()
            ->where('status', 'active')
            ->where('school_class_id', $classId)
            ->when($armId, fn ($query) => $query->where('arm_id', $armId))
            ->when($needle !== '', function ($query) use ($needle): void {
                $like = '%'.$needle.'%';
                $query->where(fn ($w) => $w->whereRaw('LOWER(first_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(other_names, '')) LIKE ?", [$like])
                    ->orWhereRaw('LOWER(student_number) LIKE ?', [$like]));
            })
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $progress = [];
        if ($students->isNotEmpty()) {
            $counts = Result::query()
                ->whereIn('student_id', $students->modelKeys())
                ->where('academic_session_id', $term->academic_session_id)
                ->where('academic_term_id', $term->id)
                ->selectRaw('student_id, status, count(*) as total')
                ->groupBy('student_id', 'status')
                ->get();
            foreach ($counts as $row) {
                $progress[$row->student_id][$row->status] = (int) $row->total;
            }
        }

        return view('portal.staff.results-class', [
            'class' => $class,
            'arm' => $armId ? $class->arms()->whereKey($armId)->first() : null,
            'term' => $term->loadMissing('session'),
            'students' => $students,
            'progress' => $progress,
            'q' => $data['q'] ?? '',
            'isAdmin' => $this->sheets->isAdmin($user),
            'canPublish' => $user->can('publish results'),
        ]);
    }

    /** One student, every subject, behaviour ratings and remarks. */
    public function studentSheet(Request $request, Student $student): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->sheets->teachesClass($user, $student->school_class_id, $student->arm_id), 403);
        $term = $this->resolveTerm($request);
        if (! $term) {
            return redirect()->route('staff.results')->withErrors(['term' => 'Create an academic session and term first.']);
        }
        $student->loadMissing(['schoolClass', 'arm', 'photo']);
        $isAdmin = $this->sheets->isAdmin($user);
        $subjects = $this->sheets->subjectsFor($student);
        if (! $isAdmin) {
            $assignedSubjectIds = TeacherAssignment::query()
                ->where('teacher_id', $this->sheets->teacherId($user) ?? 0)
                ->where('school_class_id', $student->school_class_id)
                ->where(function ($query) use ($student): void {
                    $query->whereNull('arm_id');
                    if ($student->arm_id) {
                        $query->orWhere('arm_id', $student->arm_id);
                    }
                })
                ->pluck('subject_id');
            $subjects = $subjects->whereIn('id', $assignedSubjectIds)->values();
        }
        $existing = Result::query()
            ->where('student_id', $student->id)
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->get()->keyBy('subject_id');

        $rows = $subjects->map(function ($subject) use ($user, $student, $term, $existing): array {
            $result = $existing->get($subject->id);

            return [
                'subject' => $subject,
                'result' => $result,
                'maxima' => $this->sheets->maxima((int) $term->academic_session_id, (int) $term->id, $student->school_class_id, (int) $subject->id),
                'editable' => $this->sheets->canEnter($user, $student->school_class_id, $student->arm_id, (int) $subject->id) && ($result?->status !== 'published'),
            ];
        });

        $remark = TermRemark::query()->where('student_id', $student->id)->where('academic_session_id', $term->academic_session_id)->where('academic_term_id', $term->id)->first();

        return view('portal.staff.student-sheet', [
            'student' => $student,
            'term' => $term->loadMissing('session'),
            'rows' => $rows,
            'bands' => $this->sheets->gradeBands(),
            'traits' => AffectiveTrait::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'ratings' => AffectiveRating::query()->where('student_id', $student->id)->where('academic_session_id', $term->academic_session_id)->where('academic_term_id', $term->id)->pluck('rating', 'trait_id'),
            'remark' => $remark,
            'isAdmin' => $isAdmin,
            'canRateBehaviour' => $this->sheets->canRateBehaviour($user, $student->school_class_id, $student->arm_id),
            'termOpen' => $isAdmin || $term->is_current,
        ]);
    }

    public function saveStudentSheet(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->sheets->teachesClass($user, $student->school_class_id, $student->arm_id), 403);
        $data = $request->validate([
            'term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'rows' => ['nullable', 'array'],
            'rows.*.offered' => ['nullable', 'boolean'],
            'rows.*.ca1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.ca2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.ca3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.exam' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ratings' => ['nullable', 'array'],
            'ratings.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'teacher_remark' => ['nullable', 'string', 'max:1000'],
            'principal_remark' => ['nullable', 'string', 'max:1000'],
        ]);
        $term = AcademicTerm::query()->findOrFail((int) $data['term_id']);
        $this->sheets->assertTermOpen($user, $term);
        $subjects = $this->sheets->subjectsFor($student)->keyBy('id');
        $isAdmin = $this->sheets->isAdmin($user);
        // Only an administrator or this arm's class teacher may set behaviour ratings and the class teacher's remark;
        // anything a subject teacher sends for those fields is ignored on the server.
        $canRate = $this->sheets->canRateBehaviour($user, $student->school_class_id, $student->arm_id);

        $existing = Result::query()
            ->where('student_id', $student->id)
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->get()->keyBy('subject_id');

        DB::transaction(function () use ($request, $user, $student, $term, $subjects, $data, $isAdmin, $canRate, $existing): void {
            foreach (($data['rows'] ?? []) as $subjectId => $row) {
                $subject = $subjects->get((int) $subjectId);
                abort_unless($subject, 422, 'That subject does not apply to this student.');
                $offered = array_key_exists('offered', $row) ? (bool) $row['offered'] : false;
                $filled = collect(['ca1', 'ca2', 'ca3', 'exam'])->contains(fn ($key) => isset($row[$key]) && $row[$key] !== '');
                $current = $existing->get($subject->id);
                if ($current && $current->status === 'published') {
                    continue; // published lines are read-only here
                }
                if (! $current && $offered && ! $filled) {
                    continue; // an untouched blank line must not create a row of zeros
                }
                $this->sheets->save($user, $student, $subject, $term, [
                    'ca1' => $row['ca1'] ?? null, 'ca2' => $row['ca2'] ?? null, 'ca3' => $row['ca3'] ?? null, 'exam' => $row['exam'] ?? null,
                ], $offered);
            }
            foreach (($canRate ? ($data['ratings'] ?? []) : []) as $traitId => $rating) {
                if ($rating === null || $rating === '') {
                    continue;
                }
                $trait = AffectiveTrait::query()->where('is_active', true)->find((int) $traitId);
                if (! $trait) {
                    continue;
                }
                AffectiveRating::query()->updateOrCreate(
                    ['student_id' => $student->id, 'trait_id' => $trait->id, 'academic_session_id' => $term->academic_session_id, 'academic_term_id' => $term->id],
                    ['rating' => (int) $rating],
                );
            }
            $values = [];
            if ($canRate && $request->has('teacher_remark')) {
                $values['teacher_remark'] = trim((string) ($data['teacher_remark'] ?? '')) ?: null;
            }
            if ($isAdmin && $request->has('principal_remark')) {
                $values['principal_remark'] = trim((string) ($data['principal_remark'] ?? '')) ?: null;
            }
            if ($values !== []) {
                TermRemark::query()->updateOrCreate(
                    ['student_id' => $student->id, 'academic_session_id' => $term->academic_session_id, 'academic_term_id' => $term->id],
                    $values,
                );
            }
        });

        return redirect()->route('staff.results.student', ['student' => $student->id, 'term' => $term->id])
            ->with('success', 'Saved. Results stay pending until a results administrator publishes them.');
    }

    /** A teacher's class sheet for one subject: every student, four score columns. */
    public function subjectSheet(Request $request, TeacherAssignment $assignment): View|RedirectResponse
    {
        $user = $request->user();
        $this->authoriseAssignment($user, $assignment);
        $term = $this->resolveTerm($request);
        if (! $term) {
            return redirect()->route('staff.results')->withErrors(['term' => 'Create an academic session and term first.']);
        }
        $assignment->loadMissing(['schoolClass', 'arm', 'subject']);
        $students = $this->rosterFor($assignment);
        $results = Result::query()
            ->where('subject_id', $assignment->subject_id)
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->whereIn('student_id', $students->modelKeys())
            ->get()->keyBy('student_id');

        return view('portal.staff.subject-sheet', [
            'assignment' => $assignment,
            'term' => $term->loadMissing('session'),
            'students' => $students,
            'results' => $results,
            'maxima' => $this->sheets->maxima((int) $term->academic_session_id, (int) $term->id, $assignment->school_class_id, (int) $assignment->subject_id),
            'bands' => $this->sheets->gradeBands(),
            'termOpen' => $this->sheets->isAdmin($user) || $term->is_current,
        ]);
    }

    public function saveSubjectSheet(Request $request, TeacherAssignment $assignment): RedirectResponse
    {
        $user = $request->user();
        $this->authoriseAssignment($user, $assignment);
        $data = $request->validate([
            'term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'rows' => ['required', 'array'],
            'rows.*.offered' => ['nullable', 'boolean'],
            'rows.*.ca1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.ca2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.ca3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.exam' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $term = AcademicTerm::query()->findOrFail((int) $data['term_id']);
        $this->sheets->assertTermOpen($user, $term);
        $assignment->loadMissing('subject');
        $roster = $this->rosterFor($assignment)->keyBy('id');
        $saved = 0;

        DB::transaction(function () use ($data, $roster, $assignment, $user, $term, &$saved): void {
            foreach ($data['rows'] as $studentId => $row) {
                $student = $roster->get((int) $studentId);
                abort_unless($student, 422, 'A student in the submitted sheet is not in this class.');
                $offered = array_key_exists('offered', $row) ? (bool) $row['offered'] : false;
                $filled = collect(['ca1', 'ca2', 'ca3', 'exam'])->contains(fn ($key) => isset($row[$key]) && $row[$key] !== '');
                if ($offered && ! $filled) {
                    continue; // an untouched blank line must not create a row of zeros
                }
                $published = Result::query()->where('student_id', $student->id)->where('subject_id', $assignment->subject_id)
                    ->where('academic_term_id', $term->id)->where('status', 'published')->exists();
                if ($published) {
                    continue;
                }
                $this->sheets->save($user, $student, $assignment->subject, $term, [
                    'ca1' => $row['ca1'] ?? null, 'ca2' => $row['ca2'] ?? null, 'ca3' => $row['ca3'] ?? null, 'exam' => $row['exam'] ?? null,
                ], $offered);
                $saved++;
            }
        });

        return redirect()->route('staff.results.subject', ['assignment' => $assignment->id, 'term' => $term->id])
            ->with('success', $saved.' score line(s) saved. They stay pending until a results administrator publishes them.');
    }

    public function publishClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'arm_id' => ['nullable', 'integer', 'exists:arms,id'],
            'term_id' => ['required', 'integer', 'exists:academic_terms,id'],
        ]);
        $term = AcademicTerm::query()->findOrFail((int) $data['term_id']);
        $count = $this->sheets->publishClass($request->user(), (int) $data['class_id'], ! empty($data['arm_id']) ? (int) $data['arm_id'] : null, $term);

        return back()->with('success', $count.' result(s) published. Linked students and parents can now see them.');
    }

    public function unpublishClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'arm_id' => ['nullable', 'integer', 'exists:arms,id'],
            'term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);
        $term = AcademicTerm::query()->findOrFail((int) $data['term_id']);
        $count = $this->sheets->unpublishClass($request->user(), (int) $data['class_id'], ! empty($data['arm_id']) ? (int) $data['arm_id'] : null, $term, $data['reason']);

        return back()->with('success', $count.' result(s) returned to pending for correction. Remember to publish again afterwards.');
    }

    /** Reports archive: find a student by name and open any term's report. */
    public function archive(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'session' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'term' => ['nullable', 'integer', 'exists:academic_terms,id'],
        ]);
        $user = $request->user();
        $isAdmin = $this->sheets->isAdmin($user);
        $needle = Str::lower(trim((string) ($data['q'] ?? '')));
        $sessionId = ! empty($data['session']) ? (int) $data['session'] : null;
        $termId = ! empty($data['term']) ? (int) $data['term'] : null;
        $teacherClassIds = $isAdmin ? [] : TeacherAssignment::query()->where('teacher_id', $this->sheets->teacherId($user) ?? 0)->pluck('school_class_id')->unique()->all();

        $resultFilter = fn ($query) => $query
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->when($sessionId && ! $termId, fn ($q) => $q->where('academic_session_id', $sessionId));

        $students = Student::query()->with(['schoolClass', 'arm'])
            ->when(! $isAdmin, fn ($query) => $query->whereIn('school_class_id', $teacherClassIds))
            ->when($needle !== '', function ($query) use ($needle): void {
                $like = '%'.$needle.'%';
                $query->where(fn ($w) => $w->whereRaw('LOWER(first_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(other_names, '')) LIKE ?", [$like])
                    ->orWhereRaw('LOWER(student_number) LIKE ?', [$like]));
            })
            ->whereHas('results', $resultFilter)
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(20)->withQueryString();

        $termsByStudent = [];
        if ($students->count()) {
            $rows = Result::query()->whereIn('student_id', $students->getCollection()->modelKeys())
                ->tap($resultFilter)
                ->get(['student_id', 'academic_term_id'])
                ->unique(fn ($row) => $row->student_id.'-'.$row->academic_term_id);
            $terms = AcademicTerm::query()->with('session')->whereIn('id', $rows->pluck('academic_term_id')->unique())->get()->keyBy('id');
            foreach ($rows as $row) {
                if ($terms->has($row->academic_term_id)) {
                    $termsByStudent[$row->student_id][] = $terms[$row->academic_term_id];
                }
            }
        }

        return view('portal.staff.archive', [
            'students' => $students,
            'termsByStudent' => $termsByStudent,
            'sessions' => AcademicSession::query()->with('terms')->orderByDesc('starts_on')->orderByDesc('id')->get(),
            'q' => $data['q'] ?? '',
            'sessionId' => $sessionId,
            'termId' => $termId,
            'isAdmin' => $isAdmin,
        ]);
    }

    private function authoriseAssignment($user, TeacherAssignment $assignment): void
    {
        if ($this->sheets->isAdmin($user)) {
            return;
        }
        abort_unless($user->hasRole('Teacher') && (int) $assignment->teacher_id === (int) $this->sheets->teacherId($user), 403);
    }

    /** @return \Illuminate\Support\Collection<int, Student> */
    private function rosterFor(TeacherAssignment $assignment)
    {
        return Student::query()
            ->with('photo')
            ->where('status', 'active')
            ->where('school_class_id', $assignment->school_class_id)
            ->when($assignment->arm_id, fn ($query) => $query->where('arm_id', $assignment->arm_id))
            ->orderBy('last_name')->orderBy('first_name')
            ->get();
    }

    private function resolveTerm(Request $request): ?AcademicTerm
    {
        $id = (int) $request->query('term', $request->input('term_id', 0));
        $term = $id ? AcademicTerm::query()->with('session')->find($id) : null;

        return $term
            ?? AcademicTerm::query()->with('session')->where('is_current', true)->first()
            ?? AcademicTerm::query()->with('session')->orderByDesc('academic_session_id')->orderByDesc('sequence')->first();
    }
}
