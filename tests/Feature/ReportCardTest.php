<?php

namespace Tests\Feature;

use App\Domain\Results\Services\TermReportBuilder;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\Arm;
use App\Models\ClassTeacher;
use App\Models\MediaAsset;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** The A4 portrait report card: layout, passport photograph, cumulative columns and positions. */
class ReportCardTest extends TestCase
{
    use RefreshDatabase;

    private AcademicSession $session;
    private AcademicTerm $first;
    private AcademicTerm $second;
    private SchoolClass $class;
    private Arm $arm;
    private Subject $maths;
    private Student $ada;
    private Student $bola;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();

        SchoolSettings::current()->update(['ca1_max_score' => 40, 'ca2_max_score' => 0, 'ca3_max_score' => 0, 'exam_max_score' => 60]);
        $this->session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->first = AcademicTerm::query()->create(['academic_session_id' => $this->session->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => false]);
        $this->second = AcademicTerm::query()->create(['academic_session_id' => $this->session->id, 'name' => 'Second Term', 'sequence' => 2, 'is_current' => true]);
        $this->class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $this->arm = Arm::query()->create(['name' => 'R']);
        $this->class->arms()->attach($this->arm->id);
        $this->maths = Subject::query()->create(['name' => 'Mathematics', 'status' => 'active']);
        $this->ada = Student::query()->create(['student_number' => 'S1', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $this->class->id, 'arm_id' => $this->arm->id, 'status' => 'active']);
        $this->bola = Student::query()->create(['student_number' => 'S2', 'first_name' => 'Bola', 'last_name' => 'Ajayi', 'school_class_id' => $this->class->id, 'arm_id' => $this->arm->id, 'status' => 'active']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Results Admin');
    }

    #[Test]
    public function the_report_is_a4_portrait_with_a_passport_placeholder_when_there_is_no_photo(): void
    {
        $this->line($this->ada, $this->first, 60);

        $this->actingAs($this->admin)->get(route('reports.show', ['student' => $this->ada->id, 'term' => $this->first->id]))
            ->assertOk()
            ->assertSee('size: A4 portrait', false)
            ->assertSee('Passport photograph')
            ->assertSee('Result analysis')
            ->assertDontSee('Cumulative total');
    }

    #[Test]
    public function the_passport_photograph_is_shown_on_the_printed_report(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('student-passports/ada.jpg', 'image-bytes');
        $photo = MediaAsset::query()->create(['original_name' => 'ada.jpg', 'path' => 'student-passports/ada.jpg', 'disk' => 'public', 'mime_type' => 'image/jpeg', 'size_bytes' => 11]);
        $this->ada->update(['photo_id' => $photo->id]);
        $this->line($this->ada, $this->first, 60);

        $this->actingAs($this->admin)->get(route('reports.show', ['student' => $this->ada->id, 'term' => $this->first->id]))
            ->assertOk()
            ->assertSee('student-passports/ada.jpg', false);
    }

    #[Test]
    public function second_term_reports_show_each_term_and_the_cumulative_columns(): void
    {
        $this->line($this->ada, $this->first, 60);
        $this->line($this->ada, $this->second, 80);

        $report = app(TermReportBuilder::class)->build($this->ada, $this->second, true);

        $this->assertEquals([1 => 60.0, 2 => 80.0], $report['rows'][0]['term_totals']);
        $this->assertEquals(140.0, $report['rows'][0]['cumulative_total']);
        $this->assertEquals(70.0, $report['rows'][0]['cumulative_average']);

        $this->actingAs($this->admin)->get(route('reports.show', ['student' => $this->ada->id, 'term' => $this->second->id]))
            ->assertOk()
            ->assertSee('1st term')
            ->assertSee('2nd term')
            ->assertSee('Cumulative total')
            ->assertSee('70%');
    }

    #[Test]
    public function the_cumulative_position_ranks_students_by_their_cumulative_average(): void
    {
        $this->line($this->ada, $this->first, 60);
        $this->line($this->ada, $this->second, 80);   // cumulative average 70
        $this->line($this->bola, $this->first, 90);
        $this->line($this->bola, $this->second, 70);  // cumulative average 80

        $builder = app(TermReportBuilder::class);

        $this->assertSame(2, $builder->build($this->ada, $this->second, true)['summary']['cumulative_position']);
        $this->assertSame(1, $builder->build($this->bola, $this->second, true)['summary']['cumulative_position']);
        $this->assertNull($builder->build($this->ada, $this->first, true)['summary']['cumulative_position']);
    }

    #[Test]
    public function the_class_teachers_name_appears_on_the_report(): void
    {
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('Teacher');
        $teacher = Teacher::query()->create(['user_id' => $teacherUser->id, 'first_name' => 'Tola', 'last_name' => 'Bello', 'status' => 'active']);
        ClassTeacher::query()->create(['teacher_id' => $teacher->id, 'school_class_id' => $this->class->id, 'arm_id' => $this->arm->id]);
        $this->line($this->ada, $this->first, 60);

        $this->actingAs($this->admin)->get(route('reports.show', ['student' => $this->ada->id, 'term' => $this->first->id]))
            ->assertOk()
            ->assertSee('Tola Bello');
    }

    private function line(Student $student, AcademicTerm $term, int $score): Result
    {
        return Result::query()->create([
            'student_id' => $student->id,
            'subject_id' => $this->maths->id,
            'school_class_id' => $this->class->id,
            'arm_id' => $this->arm->id,
            'academic_session_id' => $this->session->id,
            'academic_term_id' => $term->id,
            'ca1_score' => $score * 0.4,
            'ca2_score' => 0,
            'ca3_score' => 0,
            'exam_score' => $score * 0.6,
            'total_score' => $score,
            'is_offered' => true,
            'grade' => 'B',
            'remark' => 'Very Good',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
