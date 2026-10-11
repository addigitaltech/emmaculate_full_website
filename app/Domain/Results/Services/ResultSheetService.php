<?php

namespace App\Domain\Results\Services;

use App\Models\AcademicTerm;
use App\Models\AssessmentConfig;
use App\Models\AuditLog;
use App\Models\GradeBand;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Shared rules for entering, saving and publishing assessment scores. Every screen that writes a
 * result (class score sheet, student sheet) goes through save() so the rules cannot drift apart.
 */
final class ResultSheetService
{
    public function __construct(private readonly ResultCalculator $calculator)
    {
    }

    public function isAdmin(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('manage results');
    }

    public function teacherId(User $user): ?int
    {
        $id = $user->teacherProfile()->value('id');

        return $id ? (int) $id : null;
    }

    /** Admins may enter anything; a teacher only for a class, arm and subject assigned to them. */
    public function canEnter(User $user, ?int $classId, ?int $armId, int $subjectId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }
        $teacherId = $this->teacherId($user);
        if (! $teacherId || ! $user->hasRole('Teacher') || ! $classId) {
            return false;
        }

        return TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where(fn ($query) => $query->whereNull('arm_id')->when($armId, fn ($q) => $q->orWhere('arm_id', $armId)))
            ->exists();
    }

    /** True when the user teaches anything in this class (needed for behaviour ratings and the teacher remark). */
    public function teachesClass(User $user, ?int $classId, ?int $armId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }
        $teacherId = $this->teacherId($user);
        if (! $teacherId || ! $user->hasRole('Teacher') || ! $classId) {
            return false;
        }

        return TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('school_class_id', $classId)
            ->where(fn ($query) => $query->whereNull('arm_id')->when($armId, fn ($q) => $q->orWhere('arm_id', $armId)))
            ->exists();
    }

    /** Teachers may only work on the current term; administrators may correct any term. */
    public function assertTermOpen(User $user, AcademicTerm $term): void
    {
        if (! $this->isAdmin($user) && ! $term->is_current) {
            throw ValidationException::withMessages(['term' => 'Teachers may enter scores only for the current academic term.']);
        }
    }

    /** @return Collection<int, Subject> active subjects that apply to the student's class and arm */
    public function subjectsFor(Student $student): Collection
    {
        return Subject::query()
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('school_class_id')->orWhere('school_class_id', $student->school_class_id))
            ->where(fn ($query) => $query->whereNull('arm_id')->when($student->arm_id, fn ($q) => $q->orWhere('arm_id', $student->arm_id)))
            ->orderBy('name')
            ->get();
    }

    /** @return array{ca1: int, ca2: int, ca3: int, exam: int} */
    public function maxima(int $sessionId, int $termId, ?int $classId, int $subjectId): array
    {
        // One school-wide setting (Admin > Assessment & grading) applies to every class, subject and term.
        $settings = SchoolSettings::current();

        return [
            'ca1' => (int) $settings->ca1_max_score,
            'ca2' => (int) $settings->ca2_max_score,
            'ca3' => (int) $settings->ca3_max_score,
            'exam' => (int) $settings->exam_max_score,
        ];
    }

    /** @return array<int, array{min_score: int, max_score: int, grade: string, remark: string}> */
    public function gradeBands(): array
    {
        $bands = GradeBand::query()->orderBy('min_score')->get()->map(fn (GradeBand $band) => [
            'min_score' => (int) $band->min_score,
            'max_score' => (int) $band->max_score,
            'grade' => (string) $band->grade,
            'remark' => (string) $band->remark,
        ])->all();
        if ($bands === []) {
            return ResultCalculator::DEFAULT_GRADE_BANDS;
        }
        $next = 0;
        foreach ($bands as $band) {
            if ($band['min_score'] !== $next || $band['max_score'] < $band['min_score']) {
                throw ValidationException::withMessages(['grade_bands' => 'The grade-band configuration has a gap or overlap. Fix it under Results > Grade bands.']);
            }
            $next = $band['max_score'] + 1;
        }
        if ($next !== 101) {
            throw ValidationException::withMessages(['grade_bands' => 'Grade bands must cover scores from 0 through 100.']);
        }

        return $bands;
    }

    /**
     * Save one subject result for one student as "pending". A published result is never overwritten.
     *
     * @param  array{ca1?: mixed, ca2?: mixed, ca3?: mixed, exam?: mixed}  $scores
     */
    public function save(User $actor, Student $student, Subject $subject, AcademicTerm $term, array $scores, bool $offered, ?string $remark = null): Result
    {
        if (! $this->canEnter($actor, $student->school_class_id, $student->arm_id, (int) $subject->id)) {
            abort(403, 'You are not assigned to this class and subject.');
        }
        $this->assertTermOpen($actor, $term);

        $maxima = $this->maxima((int) $term->academic_session_id, (int) $term->id, $student->school_class_id, (int) $subject->id);
        if (array_sum($maxima) !== 100) {
            throw ValidationException::withMessages(['assessment' => 'The assessment maxima for '.$subject->name.' must total 100.']);
        }

        $clean = ['ca1' => 0, 'ca2' => 0, 'ca3' => 0, 'exam' => 0];
        if ($offered) {
            foreach (array_keys($clean) as $component) {
                $value = $scores[$component] ?? 0;
                $clean[$component] = ($value === null || $value === '') ? 0 : $value;
            }
        }
        try {
            $computed = $offered
                ? $this->calculator->calculate($clean, $maxima, $this->gradeBands())
                : ['scores' => $clean, 'total_score' => 0, 'grade' => null, 'remark' => 'Not Offered'];
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['scores' => $subject->name.' for '.$student->fullName().': '.$exception->getMessage()]);
        }

        return DB::transaction(function () use ($actor, $student, $subject, $term, $computed, $offered, $remark): Result {
            $key = [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'academic_session_id' => $term->academic_session_id,
                'academic_term_id' => $term->id,
            ];
            $result = Result::query()->lockForUpdate()->firstOrNew($key);
            if ($result->exists && $result->status === 'published') {
                throw ValidationException::withMessages(['result' => 'The published result for '.$student->fullName().' in '.$subject->name.' cannot be edited. Ask a results administrator to unpublish it for correction.']);
            }
            $teacherId = $this->teacherId($actor);
            $result->fill([
                'teacher_id' => $teacherId ?? $result->teacher_id,
                'school_class_id' => $student->school_class_id,
                'arm_id' => $student->arm_id,
                'ca1_score' => $computed['scores']['ca1'],
                'ca2_score' => $computed['scores']['ca2'],
                'ca3_score' => $computed['scores']['ca3'],
                'exam_score' => $computed['scores']['exam'],
                'total_score' => $computed['total_score'],
                'is_offered' => $offered,
                'grade' => $computed['grade'],
                'remark' => $computed['remark'],
                'teacher_remark' => $remark ?? $result->teacher_remark,
                'status' => 'pending',
                'published_by' => null,
                'published_at' => null,
            ])->save();
            AuditLog::record($actor, 'results.score_saved', $result, ['student_id' => $student->id, 'subject_id' => $subject->id, 'term_id' => $term->id]);

            return $result;
        });
    }

    /** Publish every pending result of a class (and arm) for a term. Returns how many were published. */
    public function publishClass(User $actor, int $classId, ?int $armId, AcademicTerm $term): int
    {
        $count = 0;
        $ids = Result::query()
            ->where('school_class_id', $classId)
            ->when($armId, fn ($query) => $query->where('arm_id', $armId))
            ->where('academic_session_id', $term->academic_session_id)
            ->where('academic_term_id', $term->id)
            ->whereIn('status', ['pending', 'draft'])
            ->pluck('id');

        DB::transaction(function () use ($ids, $actor, $term, &$count): void {
            foreach (Result::query()->whereIn('id', $ids)->lockForUpdate()->get() as $result) {
                $result->update(['status' => 'published', 'published_by' => $actor->id, 'published_at' => now()]);
                $count++;
            }
            AuditLog::record($actor, 'results.class_published', null, ['term_id' => $term->id, 'count' => $count]);
        });

        return $count;
    }

    /** Return published results to "pending" so a correction can be made; always recorded with a reason. */
    public function unpublishClass(User $actor, int $classId, ?int $armId, AcademicTerm $term, string $reason): int
    {
        $count = 0;
        DB::transaction(function () use ($actor, $classId, $armId, $term, $reason, &$count): void {
            $rows = Result::query()
                ->where('school_class_id', $classId)
                ->when($armId, fn ($query) => $query->where('arm_id', $armId))
                ->where('academic_session_id', $term->academic_session_id)
                ->where('academic_term_id', $term->id)
                ->where('status', 'published')
                ->lockForUpdate()
                ->get();
            foreach ($rows as $result) {
                $result->update(['status' => 'pending', 'published_by' => null, 'published_at' => null]);
                $count++;
            }
            AuditLog::record($actor, 'results.class_unpublished', null, ['term_id' => $term->id, 'count' => $count, 'reason' => $reason]);
        });

        return $count;
    }
}
