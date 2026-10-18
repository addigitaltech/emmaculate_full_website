<?php

namespace Tests\Feature;

use App\Domain\Identity\StudentIdGenerator;
use App\Filament\Resources\StudentResource\Pages\CreateStudent;
use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Student IDs (EAC/2627/0001) are created automatically, never repeat, and the student form needs the full profile. */
class StudentIdTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    #[Test]
    public function every_new_student_gets_a_student_id_automatically(): void
    {
        $first = Student::query()->create(['first_name' => 'Ada', 'last_name' => 'Okoro', 'status' => 'active']);
        $second = Student::query()->create(['first_name' => 'Bola', 'last_name' => 'Ajayi', 'status' => 'active']);

        $this->assertSame('EAC/2627/0001', $first->student_number);
        $this->assertSame('EAC/2627/0002', $second->student_number);
    }

    #[Test]
    public function the_prefix_comes_from_the_school_settings_so_another_school_can_use_its_own(): void
    {
        SchoolSettings::current()->forceFill(['student_id_prefix' => 'xyz'])->save();

        $student = Student::query()->create(['first_name' => 'Chi', 'last_name' => 'Eze', 'status' => 'active']);

        $this->assertSame('XYZ/2627/0001', $student->student_number);
    }

    #[Test]
    public function a_new_session_starts_its_own_running_number(): void
    {
        Student::query()->create(['first_name' => 'Ada', 'last_name' => 'Okoro', 'status' => 'active']);
        AcademicSession::query()->update(['is_active' => false]);
        AcademicSession::query()->create(['name' => '2027/2028', 'is_active' => true]);

        $student = Student::query()->create(['first_name' => 'Bola', 'last_name' => 'Ajayi', 'status' => 'active']);

        $this->assertSame('EAC/2728/0001', $student->student_number);
    }

    #[Test]
    public function a_student_id_is_never_handed_out_twice_even_if_one_already_exists(): void
    {
        Student::query()->create(['student_number' => 'EAC/2627/0001', 'first_name' => 'Old', 'last_name' => 'Record', 'status' => 'active']);

        $student = Student::query()->create(['first_name' => 'New', 'last_name' => 'Record', 'status' => 'active']);

        $this->assertSame('EAC/2627/0002', $student->student_number);
        $this->assertSame(2, Student::query()->distinct()->count('student_number'));
    }

    #[Test]
    public function older_numbers_can_be_converted_to_the_new_format(): void
    {
        Student::query()->create(['student_number' => 'OLD-77', 'first_name' => 'Old', 'last_name' => 'Number', 'status' => 'active']);
        $this->assertSame(1, StudentIdGenerator::legacyCount());

        $this->assertSame(1, StudentIdGenerator::convertLegacy());

        $this->assertSame(0, StudentIdGenerator::legacyCount());
        $this->assertTrue(StudentIdGenerator::isStandard(Student::query()->first()->student_number));
    }

    #[Test]
    public function the_session_code_is_the_short_form_of_the_session_name(): void
    {
        $this->assertSame('2627', StudentIdGenerator::sessionCode(new AcademicSession(['name' => '2026/2027'])));
        $this->assertSame('2728', StudentIdGenerator::sessionCode(new AcademicSession(['name' => '2027 - 2028'])));
    }

    #[Test]
    public function the_student_form_needs_the_whole_profile_and_assigns_the_id_itself(): void
    {
        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $arm = Arm::query()->create(['name' => 'R']);
        $class->arms()->attach($arm->id);
        $this->actingAs($this->admin);

        // Missing date of birth, class and arm: the profile cannot be created.
        Livewire::test(CreateStudent::class)
            ->fillForm(['first_name' => 'Ada', 'last_name' => 'Okoro', 'gender' => 'Female'])
            ->call('create')
            ->assertHasFormErrors(['date_of_birth' => 'required', 'school_class_id' => 'required']);
        $this->assertSame(0, Student::query()->count());

        Livewire::test(CreateStudent::class)
            ->fillForm(['first_name' => 'Ada', 'last_name' => 'Okoro', 'gender' => 'Female', 'date_of_birth' => '2014-03-17', 'school_class_id' => $class->id, 'arm_id' => $arm->id, 'admission_date' => '2026-09-14', 'status' => 'active'])
            ->call('create')
            ->assertHasNoFormErrors();

        $student = Student::query()->firstOrFail();
        $this->assertSame('EAC/2627/0001', $student->student_number);
        $this->assertSame(0, $student->parents()->count());
    }

    #[Test]
    public function a_parent_can_be_linked_while_creating_a_student_but_it_is_optional(): void
    {
        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $parent = ParentProfile::query()->create(['full_name' => 'Mrs Grace Okoro', 'email' => 'grace@example.test']);
        $this->actingAs($this->admin);

        Livewire::test(CreateStudent::class)
            ->fillForm(['first_name' => 'Ada', 'last_name' => 'Okoro', 'gender' => 'Female', 'date_of_birth' => '2014-03-17', 'school_class_id' => $class->id, 'admission_date' => '2026-09-14', 'status' => 'active', 'parents' => [$parent->id]])
            ->call('create')
            ->assertHasNoFormErrors();

        $student = Student::query()->firstOrFail();
        $this->assertSame(['Mrs Grace Okoro'], $student->parents()->pluck('full_name')->all());
    }
}
