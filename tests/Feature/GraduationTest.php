<?php

namespace Tests\Feature;

use App\Domain\Results\Services\PromotionService;
use App\Domain\Results\Services\StudentLookup;
use App\Filament\Resources\GraduatedStudentResource\Pages\ListGraduatedStudents;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\Arm;
use App\Models\ParentProfile;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Students who finish the final class (e.g. SS3) graduate into the archive and can no longer check results. */
class GraduationTest extends TestCase
{
    use RefreshDatabase;

    private AcademicSession $source;
    private AcademicSession $destination;
    private SchoolClass $ss3;
    private Arm $arm;
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
            $this->terms[$sequence] = AcademicTerm::query()->create(['academic_session_id' => $this->source->id, 'name' => $name, 'sequence' => $sequence, 'is_current' => $sequence === 3]);
        }
        $this->ss3 = SchoolClass::query()->create(['name' => 'SS 3', 'is_active' => true, 'is_graduating' => true]);
        $this->arm = Arm::query()->create(['name' => 'Blue']);
        $this->ss3->arms()->attach($this->arm->id);
        $this->maths = Subject::query()->create(['name' => 'Mathematics', 'school_class_id' => $this->ss3->id, 'status' => 'active']);
        $this->english = Subject::query()->create(['name' => 'English', 'school_class_id' => $this->ss3->id, 'status' => 'active']);
        $this->student = Student::query()->create(['student_number' => 'G-001', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $this->ss3->id, 'arm_id' => $this->arm->id, 'status' => 'active']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        SchoolSettings::query()->firstOrFail()->fill(['promotion_mode' => 'automatic', 'promotion_min_average' => 60, 'promotion_min_subjects_passed' => 2, 'public_result_check_enabled' => true])->save();
    }

    private function score(int $sequence, Subject $subject, int $score): void
    {
        Result::query()->create([
            'student_id' => $this->student->id, 'subject_id' => $subject->id, 'school_class_id' => $this->ss3->id, 'arm_id' => $this->arm->id,
            'academic_session_id' => $this->source->id, 'academic_term_id' => $this->terms[$sequence]->id,
            'ca1_score' => $score * 0.4, 'ca2_score' => 0, 'ca3_score' => 0, 'exam_score' => $score * 0.6, 'total_score' => $score,
            'is_offered' => true, 'grade' => 'B', 'remark' => 'Very Good', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    private function passingYear(): void
    {
        foreach ([1, 2, 3] as $sequence) {
            $this->score($sequence, $this->maths, 75);
            $this->score($sequence, $this->english, 70);
        }
    }

    #[Test]
    public function a_student_who_finishes_the_final_class_graduates_into_the_archive(): void
    {
        $this->passingYear();

        $record = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        $this->assertSame('graduated', $record->decision_status);
        $this->assertNull($record->destination_class_id);
        $student = $this->student->fresh();
        $this->assertSame('graduated', $student->status);
        $this->assertNotNull($student->graduated_at);
    }

    #[Test]
    public function a_student_who_does_not_meet_the_standard_waits_for_the_administrators_decision(): void
    {
        foreach ([1, 2, 3] as $sequence) {
            $this->score($sequence, $this->maths, 30);
            $this->score($sequence, $this->english, 25);
        }
        $record = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();
        $this->assertSame('review', $record->decision_status);
        $this->assertSame('active', $this->student->fresh()->status);

        app(PromotionService::class)->decide($record, $this->admin, 'graduate');

        $this->assertSame('graduated', $this->student->fresh()->status);
        $this->assertSame('graduated', $record->fresh()->decision_status);
    }

    #[Test]
    public function only_students_of_a_final_class_can_be_graduated_by_decision(): void
    {
        $this->ss3->update(['is_graduating' => false]);
        foreach ([1, 2, 3] as $sequence) {
            $this->score($sequence, $this->maths, 30);
            $this->score($sequence, $this->english, 25);
        }
        $record = app(PromotionService::class)->processTransition($this->source, $this->destination, $this->admin)->sole();

        $this->expectException(ValidationException::class);
        app(PromotionService::class)->decide($record, $this->admin, 'graduate');
    }

    #[Test]
    public function a_graduated_student_can_no_longer_sign_in_or_check_results(): void
    {
        $this->passingYear();
        $this->student->update(['status' => 'graduated', 'graduated_at' => now()]);

        $this->assertNull(app(StudentLookup::class)->find('G-001', 'OKORO'));
        $this->post(route('result-check.lookup'), ['student_id' => 'G-001', 'surname' => 'OKORO', 'academic_term_id' => $this->terms[3]->id])
            ->assertSessionHasErrors('student_id');
    }

    #[Test]
    public function parents_cannot_open_a_graduated_students_report_but_the_administrator_can(): void
    {
        $this->passingYear();
        $parent = User::factory()->create();
        $parent->assignRole('Parent');
        $profile = ParentProfile::query()->create(['user_id' => $parent->id, 'full_name' => 'Mrs Okoro', 'email' => $parent->email]);
        $profile->students()->attach($this->student->id);
        $url = route('reports.show', ['student' => $this->student->id, 'term' => $this->terms[3]->id]);

        $this->actingAs($parent)->get($url)->assertOk();

        $this->student->update(['status' => 'graduated', 'graduated_at' => now()]);

        $this->actingAs($parent)->get($url)->assertForbidden();
        $this->actingAs($this->admin)->get($url)->assertOk();
        $this->actingAs($this->admin)->get(route('staff.results.archive', ['q' => 'G-001']))->assertOk()->assertSee('Graduated');
    }

    #[Test]
    public function graduated_students_live_in_the_archive_not_in_the_active_student_list(): void
    {
        $active = Student::query()->create(['first_name' => 'Bola', 'last_name' => 'Ajayi', 'school_class_id' => $this->ss3->id, 'status' => 'active']);
        $this->student->update(['status' => 'graduated', 'graduated_at' => now()]);
        $this->actingAs($this->admin);

        Livewire::test(ListStudents::class)->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$this->student]);
        Livewire::test(ListGraduatedStudents::class)->assertCanSeeTableRecords([$this->student])->assertCanNotSeeTableRecords([$active]);
    }

    #[Test]
    public function students_can_be_graduated_in_bulk_and_restored_if_it_was_a_mistake(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListStudents::class)->callTableBulkAction('graduate', [$this->student]);
        $this->assertSame('graduated', $this->student->fresh()->status);

        Livewire::test(ListGraduatedStudents::class)->callTableAction('restore', $this->student);
        $this->assertSame('active', $this->student->fresh()->status);
        $this->assertNull($this->student->fresh()->graduated_at);
    }
}
