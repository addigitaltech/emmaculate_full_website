<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\Arm;
use App\Models\ParentProfile;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaffResultsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicTerm $term;
    private SchoolClass $class;
    private SchoolClass $otherClass;
    private Subject $maths;
    private Subject $english;
    private Student $student;
    private Student $otherStudent;
    private User $teacher;
    private TeacherAssignment $assignment;
    private User $resultsAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();

        $session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->term = AcademicTerm::query()->create(['academic_session_id' => $session->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => true]);
        $this->class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $this->otherClass = SchoolClass::query()->create(['name' => 'JSS2', 'is_active' => true]);
        $arm = Arm::query()->create(['name' => 'R']);
        $this->class->arms()->attach($arm->id);
        $this->maths = Subject::query()->create(['name' => 'Mathematics', 'status' => 'active']);
        $this->english = Subject::query()->create(['name' => 'English', 'status' => 'active']);
        $this->student = Student::query()->create(['student_number' => 'S1', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $this->class->id, 'arm_id' => $arm->id, 'status' => 'active']);
        $this->otherStudent = Student::query()->create(['student_number' => 'S2', 'first_name' => 'Bola', 'last_name' => 'Ajayi', 'school_class_id' => $this->otherClass->id, 'status' => 'active']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('Teacher');
        $teacherProfile = Teacher::query()->create(['user_id' => $this->teacher->id, 'first_name' => 'Tola', 'last_name' => 'Bello', 'status' => 'active']);
        $this->assignment = TeacherAssignment::query()->create(['teacher_id' => $teacherProfile->id, 'school_class_id' => $this->class->id, 'arm_id' => null, 'subject_id' => $this->maths->id]);

        $this->resultsAdmin = User::factory()->create();
        $this->resultsAdmin->assignRole('Results Admin');
    }

    #[Test]
    public function a_teacher_can_save_scores_for_an_assigned_class_and_subject(): void
    {
        $this->actingAs($this->teacher)->post(route('staff.results.subject.save', $this->assignment), [
            'term_id' => $this->term->id,
            'rows' => [$this->student->id => ['offered' => '1', 'ca1' => '30', 'exam' => '50']],
        ])->assertRedirect();

        $result = Result::query()->where('student_id', $this->student->id)->where('subject_id', $this->maths->id)->firstOrFail();
        $this->assertSame('pending', $result->status);
        $this->assertEquals(80.0, (float) $result->total_score);
    }

    #[Test]
    public function a_teacher_cannot_enter_scores_for_a_class_they_do_not_teach(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('staff.results.student', ['student' => $this->otherStudent->id]))
            ->assertForbidden();

        $this->actingAs($this->teacher)->get(route('staff.results.class', ['class' => $this->otherClass->id]))->assertForbidden();
    }

    #[Test]
    public function a_teacher_cannot_open_another_teachers_score_sheet(): void
    {
        $other = User::factory()->create();
        $other->assignRole('Teacher');
        Teacher::query()->create(['user_id' => $other->id, 'first_name' => 'Kemi', 'last_name' => 'Ola', 'status' => 'active']);

        $this->actingAs($other)->get(route('staff.results.subject', $this->assignment))->assertForbidden();
        $this->actingAs($other)->post(route('staff.results.subject.save', $this->assignment), ['term_id' => $this->term->id, 'rows' => [$this->student->id => ['ca1' => '10']]])->assertForbidden();
    }

    #[Test]
    public function scores_above_the_maximum_are_rejected(): void
    {
        $this->actingAs($this->teacher)->post(route('staff.results.subject.save', $this->assignment), [
            'term_id' => $this->term->id,
            'rows' => [$this->student->id => ['offered' => '1', 'ca1' => '55', 'exam' => '10']],
        ])->assertSessionHasErrors();

        $this->assertSame(0, Result::query()->count());
    }

    #[Test]
    public function students_and_parents_cannot_reach_the_staff_area(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole('Student');
        $this->actingAs($studentUser)->get(route('staff.results'))->assertForbidden();

        $parentUser = User::factory()->create();
        $parentUser->assignRole('Parent');
        $this->actingAs($parentUser)->get(route('staff.results.archive'))->assertForbidden();
    }

    #[Test]
    public function only_published_results_reach_the_linked_parent_and_unlinked_parents_are_refused(): void
    {
        $this->actingAs($this->teacher)->post(route('staff.results.subject.save', $this->assignment), [
            'term_id' => $this->term->id,
            'rows' => [$this->student->id => ['offered' => '1', 'ca1' => '30', 'exam' => '50']],
        ])->assertRedirect();

        $parentUser = User::factory()->create();
        $parentUser->assignRole('Parent');
        $parent = ParentProfile::query()->create(['user_id' => $parentUser->id, 'full_name' => 'Mr Okoro']);
        $parent->students()->attach($this->student->id);

        $stranger = User::factory()->create();
        $stranger->assignRole('Parent');
        ParentProfile::query()->create(['user_id' => $stranger->id, 'full_name' => 'Someone Else']);

        $url = route('reports.show', ['student' => $this->student->id, 'term' => $this->term->id]);

        // Pending: families cannot see it.
        $this->actingAs($parentUser)->get($url)->assertNotFound();

        // A results administrator publishes the class.
        $this->actingAs($this->resultsAdmin)->post(route('staff.results.publish-class'), [
            'class_id' => $this->class->id, 'term_id' => $this->term->id,
        ])->assertRedirect();

        $this->actingAs($parentUser)->get($url)->assertOk()->assertSee('Okoro');
        $this->actingAs($stranger)->get($url)->assertForbidden();
    }

    #[Test]
    public function a_published_line_cannot_be_changed_by_a_teacher(): void
    {
        $this->actingAs($this->teacher)->post(route('staff.results.subject.save', $this->assignment), [
            'term_id' => $this->term->id,
            'rows' => [$this->student->id => ['offered' => '1', 'ca1' => '30', 'exam' => '50']],
        ]);
        $this->actingAs($this->resultsAdmin)->post(route('staff.results.publish-class'), ['class_id' => $this->class->id, 'term_id' => $this->term->id]);

        $this->actingAs($this->teacher)->post(route('staff.results.subject.save', $this->assignment), [
            'term_id' => $this->term->id,
            'rows' => [$this->student->id => ['offered' => '1', 'ca1' => '1', 'exam' => '1']],
        ])->assertRedirect();

        $this->assertEquals(80.0, (float) Result::query()->where('student_id', $this->student->id)->value('total_score'));
    }

    #[Test]
    public function a_teacher_cannot_publish(): void
    {
        $this->actingAs($this->teacher)->post(route('staff.results.publish-class'), ['class_id' => $this->class->id, 'term_id' => $this->term->id])->assertForbidden();
    }

    #[Test]
    public function the_footer_links_to_the_admin_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Admin login');
    }
}
