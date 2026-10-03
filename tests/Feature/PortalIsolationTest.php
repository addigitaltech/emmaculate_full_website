<?php

namespace Tests\Feature;

use App\Models\ParentProfile;
use App\Models\Result;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\ResultPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function students_can_only_view_their_own_published_results(): void
    {
        $account = User::query()->create(['name' => 'Student One', 'email' => 'student-one@example.test', 'password' => 'secret-password']);
        $account->assignRole('Student');
        $student = Student::query()->create(['user_id' => $account->id, 'student_number' => 'EA-001', 'first_name' => 'Student', 'last_name' => 'One', 'status' => 'active']);
        $other = Student::query()->create(['student_number' => 'EA-002', 'first_name' => 'Student', 'last_name' => 'Two', 'status' => 'active']);
        $policy = new ResultPolicy();

        self::assertTrue($policy->view($account, $this->resultFor($student->id)));
        self::assertFalse($policy->view($account, $this->resultFor($other->id)));
        self::assertFalse($policy->view($account, $this->resultFor($student->id, 'pending')));
    }

    #[Test]
    public function parents_can_only_view_published_results_for_linked_children(): void
    {
        $account = User::query()->create(['name' => 'Parent One', 'email' => 'parent-one@example.test', 'password' => 'secret-password']);
        $account->assignRole('Parent');
        $parent = ParentProfile::query()->create(['user_id' => $account->id, 'full_name' => 'Parent One']);
        $linked = Student::query()->create(['student_number' => 'EA-101', 'first_name' => 'Linked', 'last_name' => 'Child', 'status' => 'active']);
        $unlinked = Student::query()->create(['student_number' => 'EA-102', 'first_name' => 'Other', 'last_name' => 'Child', 'status' => 'active']);
        $parent->students()->attach($linked->id, ['relationship' => 'guardian']);
        $policy = new ResultPolicy();

        self::assertTrue($policy->view($account, $this->resultFor($linked->id)));
        self::assertFalse($policy->view($account, $this->resultFor($unlinked->id)));
        self::assertFalse($policy->view($account, $this->resultFor($linked->id, 'draft')));
    }

    #[Test]
    public function each_supported_role_can_render_its_unified_dashboard(): void
    {
        $studentAccount = $this->roleUser('dashboard-student@example.test', 'Student');
        Student::query()->create(['user_id' => $studentAccount->id, 'student_number' => 'EA-DASH-001', 'first_name' => 'Dashboard', 'last_name' => 'Student', 'status' => 'active']);

        $parentAccount = $this->roleUser('dashboard-parent@example.test', 'Parent');
        ParentProfile::query()->create(['user_id' => $parentAccount->id, 'full_name' => 'Dashboard Parent']);

        $teacherAccount = $this->roleUser('dashboard-teacher@example.test', 'Teacher');
        Teacher::query()->create(['user_id' => $teacherAccount->id, 'first_name' => 'Dashboard', 'last_name' => 'Teacher', 'status' => 'active']);

        $adminAccount = $this->roleUser('dashboard-admin@example.test', 'Website Admin');

        foreach ([[$studentAccount, 'Student'], [$parentAccount, 'Parent'], [$teacherAccount, 'Teacher'], [$adminAccount, 'Admin']] as [$account, $kind]) {
            $this->actingAs($account)->get('/portal/dashboard')
                ->assertOk()
                ->assertSee('Account: '.$kind);
        }
    }

    private function roleUser(string $email, string $role): User
    {
        $user = User::query()->create(['name' => 'Dashboard Test', 'email' => $email, 'password' => 'secret-password']);
        $user->assignRole($role);
        return $user;
    }

    private function resultFor(int $studentId, string $status = 'published'): Result
    {
        return new Result(['student_id' => $studentId, 'status' => $status, 'published_at' => $status === 'published' ? now()->subMinute() : null]);
    }
}
