<?php

namespace Tests\Feature;

use App\Filament\Pages\StudentPromotions;
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
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentPromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    private AcademicSession $source;
    private AcademicSession $destination;
    private SchoolClass $sourceClass;
    private SchoolClass $destinationClass;
    private Arm $arm;
    private Student $student;
    private User $admin;
    /** @var array<int, AcademicTerm> */
    private array $terms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

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
        $this->arm = Arm::query()->create(['name' => 'Blue']);
        $this->sourceClass->arms()->attach($this->arm->id);
        $this->destinationClass->arms()->attach($this->arm->id);
        $this->sourceClass->update(['next_class_id' => $this->destinationClass->id]);
        $subject = Subject::query()->create(['name' => 'Mathematics', 'school_class_id' => $this->sourceClass->id, 'status' => 'active']);
        $this->student = Student::query()->create([
            'student_number' => 'PM-001', 'first_name' => 'Ada', 'last_name' => 'Okoro',
            'school_class_id' => $this->sourceClass->id, 'arm_id' => $this->arm->id, 'status' => 'active',
        ]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Results Admin');
        $this->actingAs($this->admin);

        SchoolSettings::query()->firstOrFail()->fill([
            'promotion_mode' => 'manual',
            'promotion_min_average' => 60,
            'promotion_min_subjects_passed' => 1,
        ])->save();

        foreach ($this->terms as $term) {
            Result::query()->create([
                'student_id' => $this->student->id,
                'subject_id' => $subject->id,
                'school_class_id' => $this->sourceClass->id,
                'arm_id' => $this->arm->id,
                'academic_session_id' => $this->source->id,
                'academic_term_id' => $term->id,
                'ca1_score' => 28,
                'ca2_score' => 0,
                'ca3_score' => 0,
                'exam_score' => 42,
                'total_score' => 70,
                'is_offered' => true,
                'grade' => 'B',
                'remark' => 'Very Good',
                'status' => 'published',
                'published_by' => $this->admin->id,
                'published_at' => now()->subMinute(),
            ]);
        }
    }

    #[Test]
    public function results_admin_can_launch_manual_transition_and_record_a_promotion_decision(): void
    {
        $this->get(StudentPromotions::getUrl())->assertOk();

        $page = Livewire::test(StudentPromotions::class)
            ->set('data.source_session_id', $this->source->id)
            ->set('data.destination_session_id', $this->destination->id)
            ->call('launch')
            ->assertHasNoErrors()
            ->assertSee('Ada Okoro')
            ->assertSee('Awaiting an administrator decision');

        $promotion = StudentPromotion::query()->where('student_id', $this->student->id)->firstOrFail();
        self::assertSame('review', $promotion->decision_status);
        self::assertSame($this->sourceClass->id, $this->student->refresh()->school_class_id);

        $page->set('manualTargets.'.$promotion->id.'.class_id', $this->destinationClass->id)
            ->set('manualTargets.'.$promotion->id.'.arm_id', $this->arm->id)
            ->call('decide', $promotion->id, 'promote')
            ->assertHasNoErrors()
            ->assertSee('promoted');

        self::assertSame('promoted', $promotion->refresh()->decision_status);
        self::assertSame($this->destinationClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function automatic_batch_launch_applies_configured_rules_without_per_student_approval(): void
    {
        SchoolSettings::query()->firstOrFail()->update([
            'promotion_mode' => 'automatic',
            'promotion_min_average' => 60,
            'promotion_min_subjects_passed' => 1,
        ]);

        Livewire::test(StudentPromotions::class)
            ->set('data.source_session_id', $this->source->id)
            ->set('data.destination_session_id', $this->destination->id)
            ->call('launch')
            ->assertHasNoErrors()
            ->assertSee('promoted');

        self::assertSame('promoted', StudentPromotion::query()->where('student_id', $this->student->id)->value('decision_status'));
        self::assertSame($this->destinationClass->id, $this->student->refresh()->school_class_id);
    }

    #[Test]
    public function teachers_cannot_open_the_student_promotion_page(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('Teacher');

        $this->actingAs($teacher)->get(StudentPromotions::getUrl())->assertForbidden();
    }
}
