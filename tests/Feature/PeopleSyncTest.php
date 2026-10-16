<?php

namespace Tests\Feature;

use App\Domain\Identity\IdentitySync;
use App\Domain\Results\Services\StudentCsvImporter;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PeopleSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function every_new_student_gets_a_student_login_and_a_rename_is_followed(): void
    {
        $student = Student::query()->create(['student_number' => 'S-1', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'status' => 'active']);

        $student->refresh();
        $this->assertNotNull($student->user_id);
        $user = User::query()->findOrFail($student->user_id);
        $this->assertTrue($user->hasRole('Student'));
        $this->assertSame('Ada Okoro', $user->name);

        $student->update(['first_name' => 'Adaeze']);
        $this->assertSame('Adaeze Okoro', $user->fresh()->name);
    }

    #[Test]
    public function students_uploaded_from_a_csv_file_get_logins_too(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'students');
        file_put_contents($path, "student_number,first_name,last_name\nC-1,Bola,Ajayi\nC-2,Chi,Eze\n");

        app(StudentCsvImporter::class)->import($path);

        $this->assertSame(0, Student::query()->whereNull('user_id')->count());
        $this->assertSame(2, User::role('Student')->count());
    }

    #[Test]
    public function teachers_and_parents_with_an_email_get_logins_with_the_right_role(): void
    {
        $teacher = Teacher::query()->create(['first_name' => 'Tola', 'last_name' => 'Bello', 'email' => 'tola@example.com', 'status' => 'active']);
        $parent = ParentProfile::query()->create(['full_name' => 'Mr Okoro', 'email' => 'okoro@example.com']);

        $this->assertTrue(User::query()->findOrFail($teacher->fresh()->user_id)->hasRole('Teacher'));
        $this->assertTrue(User::query()->findOrFail($parent->fresh()->user_id)->hasRole('Parent'));
    }

    #[Test]
    public function a_user_created_with_a_role_gets_the_matching_school_record(): void
    {
        $teacher = User::factory()->create(['name' => 'Kemi Adeyemi']);
        $teacher->assignRole('Teacher');
        $parent = User::factory()->create(['name' => 'Mrs Bello']);
        $parent->assignRole('Parent');
        $student = User::factory()->create(['name' => 'Dayo Nwosu']);
        $student->assignRole('Student');

        foreach ([$teacher, $parent, $student] as $user) {
            IdentitySync::forUser($user->fresh());
            IdentitySync::forUser($user->fresh()); // running it twice must not duplicate
        }

        $this->assertSame(1, Teacher::query()->where('user_id', $teacher->id)->count());
        $this->assertSame(1, ParentProfile::query()->where('user_id', $parent->id)->count());
        $record = Student::query()->where('user_id', $student->id)->firstOrFail();
        $this->assertSame('Nwosu', $record->last_name);
        $this->assertStringStartsWith('STU-', $record->student_number);
    }

    #[Test]
    public function the_sync_command_gives_existing_students_a_login(): void
    {
        Student::withoutEvents(fn () => Student::query()->create(['student_number' => 'OLD-1', 'first_name' => 'Old', 'last_name' => 'Student', 'status' => 'active']));
        $this->assertSame(1, Student::query()->whereNull('user_id')->count());

        $this->artisan('school:sync-people')->assertSuccessful();

        $this->assertSame(0, Student::query()->whereNull('user_id')->count());
        $this->artisan('school:sync-people')->assertSuccessful();
        $this->assertSame(1, User::role('Student')->count());
    }
}
