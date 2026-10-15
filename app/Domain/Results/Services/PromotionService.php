<?php

namespace App\Domain\Results\Services;

use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\AuditLog;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PromotionService
{
    public function __construct(private readonly ResultCalculator $calculator)
    {
    }

    /**
     * Evaluate one explicitly selected academic-session transition. Automatic mode applies only
     * eligible decisions; manual mode creates review entries without changing student assignments.
     *
     * @return Collection<int, StudentPromotion>
     */
    public function processTransition(AcademicSession $source, AcademicSession $destination, User $actor): Collection
    {
        $this->assertSchoolAdministrator($actor);

        if ((int) $source->id === (int) $destination->id) {
            throw ValidationException::withMessages(['destination_session_id' => 'Choose a different destination academic session.']);
        }

        $settings = SchoolSettings::query()->orderBy('id')->firstOrFail();
        $mode = $settings->promotion_mode ?: 'manual';
        if (! in_array($mode, ['manual', 'automatic'], true)) {
            throw ValidationException::withMessages(['promotion_mode' => 'Choose Manual or Automatic promotion in Portal Settings.']);
        }
        if ($mode === 'automatic' && ($settings->promotion_min_average === null || $settings->promotion_min_subjects_passed === null)) {
            throw ValidationException::withMessages(['promotion_mode' => 'Configure both automatic promotion thresholds in Portal Settings before launching a batch.']);
        }

        $terms = $source->terms()->get()->keyBy('sequence');
        $thirdTerm = $terms->get(3);
        if (! $thirdTerm) {
            throw ValidationException::withMessages(['source_session_id' => 'The source session does not have a third term to evaluate.']);
        }

        $termIds = $terms->only([1, 2, 3])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $studentIds = Result::query()
            ->where('academic_session_id', $source->id)
            ->whereIn('academic_term_id', $termIds)
            ->distinct()->pluck('student_id');
        $sessionClassIds = Result::query()
            ->where('academic_session_id', $source->id)
            ->whereIn('academic_term_id', $termIds)
            ->whereNotNull('school_class_id')
            ->distinct()->pluck('school_class_id');
        $studentIds = $studentIds->merge(
            Student::query()->where('status', 'active')->whereIn('school_class_id', $sessionClassIds)->pluck('id')
        )->unique()->values();

        $records = collect();
        foreach ($studentIds as $studentId) {
            $record = DB::transaction(function () use ($source, $destination, $actor, $settings, $mode, $terms, $thirdTerm, $termIds, $studentId): ?StudentPromotion {
                $student = Student::query()->whereKey($studentId)->where('status', 'active')->lockForUpdate()->first();
                if (! $student) {
                    return null;
                }

                $existing = StudentPromotion::query()
                    ->where('student_id', $student->id)
                    ->where('source_session_id', $source->id)
                    ->where('destination_session_id', $destination->id)
                    ->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }

                $results = Result::query()->with('academicTerm')
                    ->where('student_id', $student->id)
                    ->where('academic_session_id', $source->id)
                    ->whereIn('academic_term_id', $termIds)
                    ->get();
                $anchor = $results->first(fn (Result $result): bool => (int) $result->academic_term_id === (int) $thirdTerm->id)
                    ?? $results->sortByDesc(fn (Result $result): int => (int) ($result->academicTerm?->sequence ?? 0))->first();
                $sourceClassId = $anchor ? $anchor->school_class_id : $student->school_class_id;
                $sourceArmId = $anchor ? $anchor->arm_id : $student->arm_id;

                $analysis = $this->analyzeStudent($source, $terms, $results, $sourceClassId, $sourceArmId, $settings);
                $mapping = $this->nextAssignment($sourceClassId, $sourceArmId);
                $decisionStatus = 'review';
                $reviewReason = $analysis['reason'];
                $destinationClassId = $mapping['class_id'];
                $destinationArmId = $mapping['arm_id'];
                $decidedBy = null;
                $decidedAt = null;

                if ($mode === 'manual') {
                    if ($reviewReason === null) {
                        $reviewReason = $analysis['eligible'] ? 'Awaiting an administrator decision.' : $this->thresholdReason($analysis, $settings);
                    }
                } elseif (! $analysis['complete']) {
                    $reviewReason = $analysis['reason'] ?? 'Third-term records are incomplete.';
                } elseif (! $analysis['eligible']) {
                    $reviewReason = $this->thresholdReason($analysis, $settings);
                } elseif (! $mapping['valid']) {
                    $reviewReason = $mapping['reason'];
                } else {
                    $student->forceFill(['school_class_id' => $destinationClassId, 'arm_id' => $destinationArmId])->save();
                    $decisionStatus = 'promoted';
                    $reviewReason = null;
                    $decidedBy = $actor->id;
                    $decidedAt = now();
                }

                $promotion = StudentPromotion::query()->create([
                    'student_id' => $student->id,
                    'source_session_id' => $source->id,
                    'destination_session_id' => $destination->id,
                    'source_class_id' => $sourceClassId,
                    'source_arm_id' => $sourceArmId,
                    'destination_class_id' => $destinationClassId,
                    'destination_arm_id' => $destinationArmId,
                    'decision_status' => $decisionStatus,
                    'decision_mode' => $mode,
                    'eligible' => $analysis['eligible'],
                    'cumulative_average' => $analysis['average'],
                    'subjects_passed' => $analysis['subjects_passed'],
                    'subjects_offered' => $analysis['subjects_offered'],
                    'pass_mark_used' => $settings->pass_percentage,
                    'min_average_required' => $settings->promotion_min_average,
                    'min_passed_required' => $settings->promotion_min_subjects_passed,
                    'review_reason' => $reviewReason,
                    'decided_by' => $decidedBy,
                    'evaluated_at' => now(),
                    'decided_at' => $decidedAt,
                ]);

                AuditLog::record($actor, 'student_promotion.evaluated', $promotion, [
                    'source_session_id' => $source->id,
                    'destination_session_id' => $destination->id,
                    'decision_mode' => $mode,
                    'decision_status' => $decisionStatus,
                    'eligible' => $analysis['eligible'],
                    'cumulative_average' => $analysis['average'],
                    'subjects_passed' => $analysis['subjects_passed'],
                ]);

                return $promotion;
            });

            if ($record) {
                $records->push($record);
            }
        }

        AuditLog::record($actor, 'student_promotion.batch_processed', null, [
            'source_session_id' => $source->id,
            'destination_session_id' => $destination->id,
            'decision_mode' => $mode,
            'students_considered' => $records->count(),
            'promoted' => $records->filter(fn (StudentPromotion $record): bool => $record->wasRecentlyCreated && $record->decision_status === 'promoted')->count(),
            'review' => $records->filter(fn (StudentPromotion $record): bool => $record->wasRecentlyCreated && $record->decision_status === 'review')->count(),
            'already_processed' => $records->filter(fn (StudentPromotion $record): bool => ! $record->wasRecentlyCreated)->count(),
        ]);

        return $records;
    }

    public function decide(
        StudentPromotion $promotion,
        User $actor,
        string $decision,
        ?int $destinationClassId = null,
        ?int $destinationArmId = null,
    ): StudentPromotion {
        $this->assertSchoolAdministrator($actor);
        if (! in_array($decision, ['promote', 'retain'], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose Promote or Retain.']);
        }

        return DB::transaction(function () use ($promotion, $actor, $decision, $destinationClassId, $destinationArmId): StudentPromotion {
            $record = StudentPromotion::query()->whereKey($promotion->id)->lockForUpdate()->firstOrFail();
            if ($record->decision_status !== 'review') {
                throw ValidationException::withMessages(['decision' => 'Only students awaiting review can receive a manual decision.']);
            }

            $student = Student::query()->whereKey($record->student_id)->lockForUpdate()->firstOrFail();
            if ($decision === 'promote') {
                if (! $destinationClassId) {
                    throw ValidationException::withMessages(['destination_class_id' => 'Choose a destination class.']);
                }
                $destinationClass = SchoolClass::query()->where('is_active', true)->find($destinationClassId);
                if (! $destinationClass) {
                    throw ValidationException::withMessages(['destination_class_id' => 'Choose an active destination class.']);
                }
                if ((int) $destinationClass->id === (int) $record->source_class_id) {
                    throw ValidationException::withMessages(['destination_class_id' => 'Choose a different class or use Retain.']);
                }
                if ($destinationArmId && ! $destinationClass->arms()->whereKey($destinationArmId)->exists()) {
                    throw ValidationException::withMessages(['destination_arm_id' => 'Choose an arm that belongs to the destination class.']);
                }

                $student->forceFill(['school_class_id' => $destinationClass->id, 'arm_id' => $destinationArmId])->save();
                $record->fill([
                    'decision_status' => 'promoted',
                    'destination_class_id' => $destinationClass->id,
                    'destination_arm_id' => $destinationArmId,
                ]);
            } else {
                if (! $record->source_class_id) {
                    throw ValidationException::withMessages(['decision' => 'The historic source class is unavailable, so this student cannot be retained automatically. Choose Promote to assign a current class.']);
                }
                $student->forceFill(['school_class_id' => $record->source_class_id, 'arm_id' => $record->source_arm_id])->save();
                $record->fill([
                    'decision_status' => 'retained',
                    'destination_class_id' => $record->source_class_id,
                    'destination_arm_id' => $record->source_arm_id,
                ]);
            }

            $record->fill(['decided_by' => $actor->id, 'decided_at' => now(), 'review_reason' => null])->save();
            AuditLog::record($actor, 'student_promotion.manually_decided', $record, [
                'decision' => $decision,
                'destination_class_id' => $record->destination_class_id,
                'destination_arm_id' => $record->destination_arm_id,
            ]);

            return $record->refresh();
        });
    }

    /**
     * @param Collection<int, \App\Models\AcademicTerm> $terms
     * @param Collection<int, Result> $results
     * @return array{complete: bool, eligible: bool, average: ?float, subjects_passed: int, subjects_offered: int, reason: ?string}
     */
    private function analyzeStudent(
        AcademicSession $source,
        Collection $terms,
        Collection $results,
        ?int $sourceClassId,
        ?int $sourceArmId,
        SchoolSettings $settings,
    ): array {
        if (! $sourceClassId) {
            return ['complete' => false, 'eligible' => false, 'average' => null, 'subjects_passed' => 0, 'subjects_offered' => 0, 'reason' => 'No historic source class is available for this student.'];
        }

        $subjects = Subject::query()->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('school_class_id')->orWhere('school_class_id', $sourceClassId))
            ->where(fn ($query) => $query->whereNull('arm_id')->when($sourceArmId, fn ($armQuery) => $armQuery->orWhere('arm_id', $sourceArmId)))
            ->orderBy('name')->get();
        if ($subjects->isEmpty()) {
            return ['complete' => false, 'eligible' => false, 'average' => null, 'subjects_passed' => 0, 'subjects_offered' => 0, 'reason' => 'No active subjects are configured for the historic class and arm.'];
        }

        $issues = [];
        $subjectAverages = [];
        $subjectsPassed = 0;
        foreach ([1, 2, 3] as $sequence) {
            if (! $terms->has($sequence)) {
                $issues[] = 'The source session is missing term '.$sequence.'.';
            }
        }

        foreach ($subjects as $subject) {
            $termScores = [];
            foreach ([1, 2, 3] as $sequence) {
                $term = $terms->get($sequence);
                if (! $term) {
                    continue;
                }
                $result = $results->first(fn (Result $row): bool => (int) $row->subject_id === (int) $subject->id && (int) $row->academic_term_id === (int) $term->id);
                if (! $result) {
                    $issues[] = 'Missing '.$term->name.' result for '.$subject->name.'.';
                    continue;
                }
                if (! $this->isPublished($result)) {
                    $issues[] = 'Unpublished '.$term->name.' result for '.$subject->name.'.';
                    continue;
                }
                if ($result->is_offered) {
                    $termScores[] = (float) $result->total_score;
                }
            }

            if ($termScores !== []) {
                $average = $this->calculator->cumulativeAverage($termScores)['cumulative_average'];
                $subjectAverages[] = (float) $average;
                if ($average >= (float) $settings->pass_percentage) {
                    $subjectsPassed++;
                }
            }
        }

        if ($subjectAverages === []) {
            $issues[] = 'No offered subject scores are available through third term.';
        }

        $complete = $issues === [] && $subjectAverages !== [];
        $average = $subjectAverages === [] ? null : round(array_sum($subjectAverages) / count($subjectAverages), 2);
        $hasThresholds = $settings->promotion_min_average !== null && $settings->promotion_min_subjects_passed !== null;
        $eligible = $complete && $hasThresholds
            && $average >= (float) $settings->promotion_min_average
            && $subjectsPassed >= (int) $settings->promotion_min_subjects_passed;

        return [
            'complete' => $complete,
            'eligible' => $eligible,
            'average' => $average,
            'subjects_passed' => $subjectsPassed,
            'subjects_offered' => count($subjectAverages),
            'reason' => $issues === [] ? null : implode(' ', array_slice($issues, 0, 6)).(count($issues) > 6 ? ' More incomplete records exist.' : ''),
        ];
    }

    /** @return array{valid: bool, class_id: ?int, arm_id: ?int, reason: ?string} */
    private function nextAssignment(?int $sourceClassId, ?int $sourceArmId): array
    {
        $sourceClass = $sourceClassId ? SchoolClass::query()->with('nextClass')->find($sourceClassId) : null;
        $destinationClass = $sourceClass?->nextClass;
        if (! $destinationClass || ! $destinationClass->is_active) {
            return ['valid' => false, 'class_id' => null, 'arm_id' => null, 'reason' => 'No active next class is configured for the source class.'];
        }
        if ($sourceArmId && ! $destinationClass->arms()->whereKey($sourceArmId)->exists()) {
            return ['valid' => false, 'class_id' => $destinationClass->id, 'arm_id' => null, 'reason' => 'The source arm is not configured in the next class; manual review is required.'];
        }

        return ['valid' => true, 'class_id' => (int) $destinationClass->id, 'arm_id' => $sourceArmId, 'reason' => null];
    }

    private function thresholdReason(array $analysis, SchoolSettings $settings): string
    {
        if ($settings->promotion_min_average === null || $settings->promotion_min_subjects_passed === null) {
            return 'Automatic promotion thresholds are not configured; administrator review is required.';
        }

        return sprintf(
            'Does not meet the configured promotion rules: cumulative average %s/100 (minimum %s) and %d passed subjects (minimum %d).',
            $analysis['average'] === null ? 'unavailable' : number_format($analysis['average'], 2),
            number_format((float) $settings->promotion_min_average, 2),
            $analysis['subjects_passed'],
            (int) $settings->promotion_min_subjects_passed,
        );
    }

    private function isPublished(Result $result): bool
    {
        return $result->status === 'published' && $result->published_at !== null && $result->published_at->lte(now());
    }

    private function assertSchoolAdministrator(User $actor): void
    {
        if (! $actor->hasRole('Super Admin') && ! $actor->can('manage students')) {
            abort(403, 'You are not authorized to manage student promotions.');
        }
    }
}
