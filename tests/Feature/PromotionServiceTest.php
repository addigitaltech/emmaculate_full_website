<?php

namespace Tests\Feature;

use App\Domain\Results\Services\PromotionService;
use App\Domain\Results\Services\TermReportBuilder;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\Arm;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademicSession $source;
    private AcademicSession $destination;
    private SchoolClass $sourceClass;
    private SchoolClass $destinationClass;
    private Arm $sourceArm;
    private Arm $destinationArm;
    private Subject $maths;
    private Subject $english;
    private Student $student;
    private User $admin;
    /** @var array<int, AcademicTerm> */
    private array $terms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();

        $this->source = AcademicSession::query()->create(['name' => '2025/2026', 'is_active' => true]);
        $this->destination = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => false]);
        foreach ([1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'] as $sequence => $name) {
            $this->terms[$sequence] = AcademicTerm::query()->create([
                'academic_session_id' => $this->source->id,
                'name' => $name,
                'sequence' => $sequence,
                'is_current' => false,
            ]);
        }

        $this->sourceClass = SchoolClass::query()->create(['name' => 'JSS 1', 'is_active' => true]);
        $this->destinationClass = SchoolClass::query()->create(['name' => 'JSS 2', 'is_active' => true]);
        $this->sourceArm = Arm::query()->create(['name' => 'Blue']);
        $this->destinationArm = $this->sourceArm;
        $this->sourceClass->arms()->attach($this->sourceArm->id);
        $this->destinationClass->arms()->attach($this->destinationArm->id);
        $this->sourceClass->update(['next_class_id' => $this->destinationClass->id]);
        $this->maths = Subject::query()->create(['name' => 'Mathematics', 'school_class_id' => $this->sourceClass->id, 'status' => 'active']);
        $this->english = Subject::query()->create(['name' => 'English', 'school_class_id' => $this->sourceClass->id, 'status' => 'active']);
        $this->student = Student::query()->create([
            'student_number' => 'P-001', 'first_name' => 'Ada', 'last_name' => 'Okoro',
            'school_class_id' => $this->sourceClass->id, 'arm_id' => $this->sourceArm->id, 'status' => 'active',
        ]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Results Admin');

        SchoolSettings::query()->firstOrFail()->fill([
            'promotion_mode' => 'automatic',
            'promotion_min_average' => 60,
            'promotion_min_subjects_passed' => 2,
        ])->save();
    }

    #[Test]
    public function automatic_mode_uses_cumulative_subject_averages_and_pass_count(): void
    {
        $this->addPublishedScores([
            1 => [$this->maths->id => 60, $this->english->id => 30],
            2 => [$this->maths->id => 70, $this->english->id => 50],
            3 => [$this->maths->id => 80, $this->english->id => 70],
        ]);

        $records = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin);
        $promotion = $records->sole();

        self::assertSame('promoted', $promotion->decision_status);
        self::assertEquals(60.0, (float) $promotion->cumulative_average);
        self::assertSame(2, (int) $promotion->subjects_passed);
        self::assertSame(2, (int) $promotion->subjects_offered);
        self::assertSame(40.0, (float) $promotion->pass_mark_used);
        self::assertSame($this->destinationClass->id, $this->student->refresh()->school_class_id);
        self::assertSame($this->destinationArm->id, $this->student->arm_id);
        self::assertSame(6, Result::query()->where('student_id', $this->student->id)->count());
        self::assertSame(6, Result::query()->where('student_id', $this->student->id)->where('status', 'published')->where('school_class_id', $this->sourceClass->id)->count());

        $report = app(TermReportBuilder::class)->build($this->student->refresh(), $this->terms[3], false);
        self::assertSame($this->sourceClass->id, $report['reportClass']->id);
        self::assertSame($this->sourceArm->id, $report['reportArm']->id);
        self::assertSame('published', $report['rows'][0]['status']);
    }

    #[Test]
    public function missing_or_unpublished_term_results_are_flagged_and_do_not_promote(): void
    {
        $this->addPublishedScores([
            1 => [$this->maths->id => 80, $this->english->id => 80],
            2 => [$this->english->id => 80],
            3 => [$this->maths->id => 80, $this->english->id => 80],
        ]);

        $promotion = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        self::assertSame('review', $promotion->decision_status);
        self::assertStringContainsString('missing', strtolower((string) $promotion->review_reason));
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function students_below_the_subject_pass_count_threshold_are_flagged(): void
    {
        SchoolSettings::query()->firstOrFail()->update(['promotion_min_average' => 60, 'promotion_min_subjects_passed' => 3]);
        $this->addPublishedScores($this->perfectScores());

        $promotion = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        self::assertSame('review', $promotion->decision_status);
        self::assertFalse((bool) $promotion->eligible);
        self::assertEquals(90.0, (float) $promotion->cumulative_average);
        self::assertSame(2, (int) $promotion->subjects_passed);
        self::assertStringContainsString('2 passed subjects (minimum 3)', (string) $promotion->review_reason);
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function unpublished_results_are_flagged_for_review(): void
    {
        $this->addPublishedScores($this->perfectScores());
        Result::query()->where('student_id', $this->student->id)
            ->where('academic_term_id', $this->terms[2]->id)
            ->where('subject_id', $this->maths->id)
            ->update(['status' => 'pending', 'published_by' => null, 'published_at' => null]);

        $promotion = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        self::assertSame('review', $promotion->decision_status);
        self::assertStringContainsString('unpublished', strtolower((string) $promotion->review_reason));
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function active_classmates_with_no_result_rows_are_included_for_review(): void
    {
        $this->addPublishedScores($this->perfectScores());
        $blankStudent = Student::query()->create([
            'student_number' => 'P-002', 'first_name' => 'Bola', 'last_name' => 'Ajayi',
            'school_class_id' => $this->sourceClass->id, 'arm_id' => $this->sourceArm->id, 'status' => 'active',
        ]);

        $records = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin);
        $promotion = $records->firstWhere('student_id', $blankStudent->id);

        self::assertNotNull($promotion);
        self::assertSame('review', $promotion->decision_status);
        self::assertStringContainsString('missing', strtolower((string) $promotion->review_reason));
        self::assertSame($this->sourceClass->id, $blankStudent->refresh()->school_class_id);
    }

    #[Test]
    public function automatic_mode_flags_students_when_progression_or_arm_mapping_is_missing(): void
    {
        $this->addPublishedScores($this->perfectScores());
        $this->sourceClass->update(['next_class_id' => null]);

        $promotion = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        self::assertSame('review', $promotion->decision_status);
        self::assertStringContainsString('next class', strtolower((string) $promotion->review_reason));
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function automatic_mode_flags_a_source_arm_missing_from_the_next_class(): void
    {
        $this->addPublishedScores($this->perfectScores());
        $this->destinationClass->arms()->detach($this->sourceArm->id);
        $otherArm = Arm::query()->create(['name' => 'Gold']);
        $this->destinationClass->arms()->attach($otherArm->id);

        $promotion = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        self::assertSame('review', $promotion->decision_status);
        self::assertStringContainsString('source arm', strtolower((string) $promotion->review_reason));
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function manual_mode_requires_a_decision_and_can_promote_or_retain(): void
    {
        SchoolSettings::query()->firstOrFail()->update(['promotion_mode' => 'manual']);
        $this->addPublishedScores($this->perfectScores());
        $service = app(PromotionService::class);
        $promotion = $service->processTransition($this->source, $this->destination, $this->admin)->sole();

        self::assertSame('review', $promotion->decision_status);
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);

        $decided = $service->decide($promotion, $this->admin, 'promote', $this->destinationClass->id, $this->destinationArm->id);

        self::assertSame('promoted', $decided->decision_status);
        self::assertSame($this->destinationClass->id, $this->student->refresh()->school_class_id);
        self::assertSame($this->destinationArm->id, $this->student->arm_id);
    }

    #[Test]
    public function a_manual_retain_decision_keeps_the_historic_class_and_arm(): void
    {
        SchoolSettings::query()->firstOrFail()->update(['promotion_mode' => 'manual']);
        $this->addPublishedScores($this->perfectScores());
        $service = app(PromotionService::class);
        $promotion = $service->processTransition($this->source, $this->destination, $this->admin)->sole();

        $decided = $service->decide($promotion, $this->admin, 'retain');

        self::assertSame('retained', $decided->decision_status);
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);
        self::assertSame($this->sourceArm->id, $this->student->arm_id);
        self::assertSame($this->sourceClass->id, $decided->destination_class_id);
    }

    #[Test]
    public function manual_promote_must_choose_a_different_class_than_the_source(): void
    {
        SchoolSettings::query()->firstOrFail()->update(['promotion_mode' => 'manual']);
        $this->addPublishedScores($this->perfectScores());
        $service = app(PromotionService::class);
        $promotion = $service->processTransition($this->source, $this->destination, $this->admin)->sole();

        $this->expectException(ValidationException::class);
        $service->decide($promotion, $this->admin, 'promote', $this->sourceClass->id, $this->sourceArm->id);
    }

    #[Test]
    public function repeating_a_transition_does_not_create_or_apply_a_second_promotion(): void
    {
        $this->addPublishedScores($this->perfectScores());
        $service = app(PromotionService::class);
        $service->processTransition($this->source, $this->destination, $this->admin);
        $again = $service->processTransition($this->source, $this->destination, $this->admin);

        self::assertSame(1, StudentPromotion::query()->where('student_id', $this->student->id)->count());
        self::assertSame(1, $again->count());
        self::assertSame($this->destinationClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function same_session_transitions_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(PromotionService::class)->processTransition($this->source, $this->source, $this->admin);
    }

    #[Test]
    public function a_source_session_without_a_third_term_cannot_be_processed(): void
    {
        $this->terms[3]->delete();

        $this->expectException(ValidationException::class);
        app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin);
    }

    /** @param array<int, array<int, int>> $scores */
    private function addPublishedScores(array $scores, array $pending = []): void
    {
        foreach ($scores as $sequence => $subjectScores) {
            foreach ($subjectScores as $subjectId => $score) {
                $this->createResult($this->terms[$sequence], (int) $subjectId, $score, 'published');
            }
        }
        foreach ($pending as $sequence => $subjectIds) {
            foreach ($subjectIds as $subjectId) {
                $this->createResult($this->terms[$sequence], (int) $subjectId, 70, 'pending');
            }
        }
    }

    private function createResult(AcademicTerm $term, int $subjectId, int $score, string $status): Result
    {
        return Result::query()->create([
            'student_id' => $this->student->id,
            'subject_id' => $subjectId,
            'school_class_id' => $this->sourceClass->id,
            'arm_id' => $this->sourceArm->id,
            'academic_session_id' => $this->source->id,
            'academic_term_id' => $term->id,
            'ca1_score' => $score * 0.4,
            'ca2_score' => 0,
            'ca3_score' => 0,
            'exam_score' => $score * 0.6,
            'total_score' => $score,
            'is_offered' => true,
            'grade' => 'A',
            'remark' => 'Good',
            'status' => $status,
            'published_by' => $status === 'published' ? $this->admin->id : null,
            'published_at' => $status === 'published' ? now()->subMinute() : null,
        ]);
    }

    /** @return array<int, array<int, int>> */
    private function perfectScores(): array
    {
        return [
            1 => [$this->maths->id => 90, $this->english->id => 90],
            2 => [$this->maths->id => 90, $this->english->id => 90],
            3 => [$this->maths->id => 90, $this->english->id => 90],
        ];
    }
}
