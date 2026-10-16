<?php

namespace Tests\Feature;

use App\Domain\Results\Services\ResultSheetService;
use App\Domain\Results\Services\TermReportBuilder;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\Arm;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Proves that one student's results never change another's, and that publishing recomputes safely. */
class ResultIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private AcademicTerm $term;
    private SchoolClass $class;
    private Subject $maths;
    private Subject $english;
    private Student $ada;
    private Student $bola;
    private Student $chika;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();

        SchoolSettings::current()->update(['ca1_max_score' => 40, 'ca2_max_score' => 0, 'ca3_max_score' => 0, 'exam_max_score' => 60]);
        $session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->term = AcademicTerm::query()->create(['academic_session_id' => $session->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => true]);
        $this->class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $arm = Arm::query()->create(['name' => 'R']);
        $this->class->arms()->attach($arm->id);
        $this->maths = Subject::query()->create(['name' => 'Mathematics', 'status' => 'active']);
        $this->english = Subject::query()->create(['name' => 'English', 'status' => 'active']);
        $this->ada = $this->student('S1', 'Ada', 'Okoro', $arm->id);
        $this->bola = $this->student('S2', 'Bola', 'Ajayi', $arm->id);
        $this->chika = $this->student('S3', 'Chika', 'Nwosu', $arm->id);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    #[Test]
    public function positions_rank_among_every_entered_result_published_or_not(): void
    {
        $this->line($this->ada, $this->maths, 80, 'published');
        $this->line($this->ada, $this->english, 70, 'published');
        $this->line($this->bola, $this->maths, 95, 'pending');
        $this->line($this->bola, $this->english, 90, 'pending');

        $report = app(TermReportBuilder::class)->build($this->ada, $this->term, false);

        $this->assertSame(2, $report['summary']['class_total']);
        $this->assertSame(2, $report['summary']['class_position']);
        $this->assertSame(2, $report['rows'][0]['position']);
    }

    #[Test]
    public function saving_one_students_scores_never_changes_another_students_results(): void
    {
        $sheets = app(ResultSheetService::class);
        $sheets->save($this->admin, $this->ada, $this->maths, $this->term, ['ca1' => 30, 'exam' => 50], true);
        $before = Result::query()->where('student_id', $this->ada->id)->first()->only(['total_score', 'grade', 'status', 'ca1_score', 'exam_score']);

        $sheets->save($this->admin, $this->bola, $this->maths, $this->term, ['ca1' => 10, 'exam' => 20], true);
        $sheets->save($this->admin, $this->bola, $this->maths, $this->term, ['ca1' => 40, 'exam' => 60], true);

        $after = Result::query()->where('student_id', $this->ada->id)->first()->only(['total_score', 'grade', 'status', 'ca1_score', 'exam_score']);
        $this->assertEquals($before, $after);
        $this->assertSame(2, Result::query()->count());
    }

    #[Test]
    public function equal_averages_share_a_position(): void
    {
        $this->line($this->ada, $this->maths, 75, 'pending');
        $this->line($this->bola, $this->maths, 75, 'pending');
        $this->line($this->chika, $this->maths, 60, 'pending');

        $builder = app(TermReportBuilder::class);
        $this->assertSame(1, $builder->build($this->ada, $this->term, true)['summary']['class_position']);
        $this->assertSame(1, $builder->build($this->bola, $this->term, true)['summary']['class_position']);
        $this->assertSame(3, $builder->build($this->chika, $this->term, true)['summary']['class_position']);
    }

    #[Test]
    public function publishing_recomputes_a_stale_total_and_grade(): void
    {
        $result = $this->line($this->ada, $this->maths, 80, 'pending');
        $result->update(['total_score' => 5, 'grade' => 'F', 'remark' => 'Fail']);

        app(ResultSheetService::class)->publishClass($this->admin, $this->class->id, null, $this->term);

        $result->refresh();
        $this->assertSame('published', $result->status);
        $this->assertEquals(80.0, (float) $result->total_score);
        $this->assertNotSame('F', $result->grade);
    }

    #[Test]
    public function publishing_is_blocked_when_a_saved_score_no_longer_fits_the_current_settings(): void
    {
        $this->line($this->ada, $this->maths, 80, 'pending'); // ca1 32 of 40
        SchoolSettings::current()->update(['ca1_max_score' => 20, 'ca2_max_score' => 0, 'ca3_max_score' => 0, 'exam_max_score' => 80]);

        try {
            app(ResultSheetService::class)->publishClass($this->admin, $this->class->id, null, $this->term);
            $this->fail('Publishing should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('publish', $exception->errors());
        }

        $this->assertSame('pending', Result::query()->first()->status);
    }

    #[Test]
    public function a_student_moved_to_another_class_keeps_every_subject_on_the_report(): void
    {
        $this->line($this->ada, $this->maths, 80, 'published');
        $this->line($this->ada, $this->english, 70, 'published');
        $other = SchoolClass::query()->create(['name' => 'JSS2', 'is_active' => true]);
        $this->ada->update(['school_class_id' => $other->id, 'arm_id' => null]);
        Result::query()->where('student_id', $this->ada->id)->where('subject_id', $this->english->id)->update(['school_class_id' => $other->id]);

        $report = app(TermReportBuilder::class)->build($this->ada->fresh(), $this->term, false);

        $this->assertCount(2, $report['rows']);
    }

    private function student(string $number, string $first, string $last, int $armId): Student
    {
        return Student::query()->create(['student_number' => $number, 'first_name' => $first, 'last_name' => $last, 'school_class_id' => $this->class->id, 'arm_id' => $armId, 'status' => 'active']);
    }

    private function line(Student $student, Subject $subject, int $score, string $status): Result
    {
        return Result::query()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'school_class_id' => $this->class->id,
            'arm_id' => $student->arm_id,
            'academic_session_id' => $this->term->academic_session_id,
            'academic_term_id' => $this->term->id,
            'ca1_score' => $score * 0.4,
            'ca2_score' => 0,
            'ca3_score' => 0,
            'exam_score' => $score * 0.6,
            'total_score' => $score,
            'is_offered' => true,
            'grade' => 'B',
            'remark' => 'Very Good',
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}
