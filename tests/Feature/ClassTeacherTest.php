<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\AffectiveRating;
use App\Models\AffectiveTrait;
use App\Models\Arm;
use App\Models\ClassTeacher;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TermRemark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Only an arm's class teacher (or an administrator) may set behaviour ratings and the class teacher's remark. */
class ClassTeacherTest extends TestCase
{
    use RefreshDatabase;

    private AcademicTerm $term;
    private SchoolClass $class;
    private Arm $armR;
    private Arm $armB;
    private Student $student;
    private AffectiveTrait $trait;
    private Teacher $classTeacher;
    private User $classTeacherUser;
    private Teacher $subjectTeacher;
    private User $subjectTeacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();

        $session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->term = AcademicTerm::query()->create(['academic_session_id' => $session->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => true]);
        $this->class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $this->armR = Arm::query()->create(['name' => 'R']);
        $this->armB = Arm::query()->create(['name' => 'B']);
        $this->class->arms()->attach([$this->armR->id, $this->armB->id]);
        $maths = Subject::query()->create(['name' => 'Mathematics', 'status' => 'active']);
        $this->student = Student::query()->create(['student_number' => 'S1', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $this->class->id, 'arm_id' => $this->armR->id, 'status' => 'active']);
        $this->trait = AffectiveTrait::query()->firstOrCreate(['name' => 'Punctuality'], ['category' => 'affective', 'is_active' => true, 'sort_order' => 1]);

        [$this->classTeacherUser, $this->classTeacher] = $this->teacher('Tola', 'Bello');
        [$this->subjectTeacherUser, $this->subjectTeacher] = $this->teacher('Sade', 'Eze');
        TeacherAssignment::query()->create(['teacher_id' => $this->subjectTeacher->id, 'school_class_id' => $this->class->id, 'arm_id' => null, 'subject_id' => $maths->id]);
        ClassTeacher::query()->create(['teacher_id' => $this->classTeacher->id, 'school_class_id' => $this->class->id, 'arm_id' => $this->armR->id]);
    }

    #[Test]
    public function each_arm_has_only_one_class_teacher(): void
    {
        $this->expectException(ValidationException::class);

        ClassTeacher::query()->create(['teacher_id' => $this->subjectTeacher->id, 'school_class_id' => $this->class->id, 'arm_id' => $this->armR->id]);
    }

    #[Test]
    public function one_teacher_can_be_class_teacher_of_several_arms(): void
    {
        ClassTeacher::query()->create(['teacher_id' => $this->classTeacher->id, 'school_class_id' => $this->class->id, 'arm_id' => $this->armB->id]);

        $this->assertSame(2, ClassTeacher::query()->where('teacher_id', $this->classTeacher->id)->count());
    }

    #[Test]
    public function a_class_with_arms_needs_an_arm_for_its_class_teacher(): void
    {
        $this->expectException(ValidationException::class);

        ClassTeacher::query()->create(['teacher_id' => $this->subjectTeacher->id, 'school_class_id' => $this->class->id, 'arm_id' => null]);
    }

    #[Test]
    public function the_class_teacher_can_save_ratings_and_the_class_teacher_remark(): void
    {
        $this->actingAs($this->classTeacherUser)->post(route('staff.results.student.save', $this->student), [
            'term_id' => $this->term->id,
            'ratings' => [$this->trait->id => 4],
            'teacher_remark' => 'A hard-working pupil.',
        ])->assertRedirect();

        $this->assertSame(4, (int) AffectiveRating::query()->where('student_id', $this->student->id)->value('rating'));
        $this->assertSame('A hard-working pupil.', TermRemark::query()->where('student_id', $this->student->id)->value('teacher_remark'));
    }

    #[Test]
    public function a_subject_teacher_who_is_not_the_class_teacher_cannot_save_ratings_or_the_remark(): void
    {
        $this->actingAs($this->subjectTeacherUser)->post(route('staff.results.student.save', $this->student), [
            'term_id' => $this->term->id,
            'ratings' => [$this->trait->id => 5],
            'teacher_remark' => 'Should not be saved.',
        ])->assertRedirect();

        $this->assertSame(0, AffectiveRating::query()->count());
        $this->assertSame(0, TermRemark::query()->count());
    }

    #[Test]
    public function the_class_teacher_of_another_arm_cannot_open_this_students_sheet(): void
    {
        $otherArmStudent = Student::query()->create(['student_number' => 'S2', 'first_name' => 'Bola', 'last_name' => 'Ajayi', 'school_class_id' => $this->class->id, 'arm_id' => $this->armB->id, 'status' => 'active']);

        $this->actingAs($this->classTeacherUser)
            ->get(route('staff.results.student', ['student' => $otherArmStudent->id, 'term' => $this->term->id]))
            ->assertForbidden();
    }

    #[Test]
    public function the_student_sheet_shows_behaviour_fields_only_to_the_class_teacher_and_administrators(): void
    {
        $url = route('staff.results.student', ['student' => $this->student->id, 'term' => $this->term->id]);

        $this->actingAs($this->classTeacherUser)->get($url)->assertOk()->assertSee('Skills and behaviour');
        $this->actingAs($this->subjectTeacherUser)->get($url)->assertOk()->assertDontSee('Skills and behaviour');

        $admin = User::factory()->create();
        $admin->assignRole('Results Admin');
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Skills and behaviour');
    }

    /** @return array{0: User, 1: Teacher} */
    private function teacher(string $first, string $last): array
    {
        $user = User::factory()->create();
        $user->assignRole('Teacher');
        $teacher = Teacher::query()->create(['user_id' => $user->id, 'first_name' => $first, 'last_name' => $last, 'status' => 'active']);

        return [$user, $teacher];
    }
}
