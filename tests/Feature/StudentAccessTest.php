<?php

namespace Tests\Feature;

use App\Domain\Results\Services\StudentPinService;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\ParentProfile;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentAccessTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private AcademicTerm $term;
    private string $pin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        RateLimiter::clear('student-login:'.hash('sha256', 'ADM-001|127.0.0.1'));
        RateLimiter::clear('result-check:'.hash('sha256', 'ADM-001|127.0.0.1'));

        $session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->term = AcademicTerm::query()->create(['academic_session_id' => $session->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => true]);
        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $subject = Subject::query()->create(['name' => 'Mathematics', 'status' => 'active']);
        $this->student = Student::query()->create(['student_number' => 'ADM-001', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $class->id, 'status' => 'active']);
        $this->pin = app(StudentPinService::class)->issue($this->student);
        Result::query()->create([
            'student_id' => $this->student->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id,
            'academic_session_id' => $session->id, 'academic_term_id' => $this->term->id,
            'ca1_score' => 8, 'ca2_score' => 0, 'ca3_score' => 0, 'exam_score' => 60, 'total_score' => 68,
            'is_offered' => true, 'grade' => 'B', 'remark' => 'Very Good', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    private function settings(array $values): void
    {
        SchoolSettings::current()->forceFill($values)->save();
    }

    private function publishResultFor(AcademicTerm $term): void
    {
        $subject = Subject::query()->firstOrFail();
        Result::query()->create([
            'student_id' => $this->student->id, 'subject_id' => $subject->id, 'school_class_id' => $this->student->school_class_id,
            'academic_session_id' => $term->academic_session_id, 'academic_term_id' => $term->id,
            'ca1_score' => 10, 'ca2_score' => 0, 'ca3_score' => 0, 'exam_score' => 40, 'total_score' => 50,
            'is_offered' => true, 'grade' => 'C', 'remark' => 'Good', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    #[Test]
    public function a_student_can_sign_in_with_student_id_and_surname_and_gets_an_account(): void
    {
        $this->post(route('student.login'), ['student_id' => 'adm-001', 'surname' => 'OKORO', 'pin' => $this->pin])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticated();
        $user = $this->student->fresh()->user;
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Student'));
        $this->assertSame(1, $user->getRoleNames()->count());
    }

    #[Test]
    public function the_surname_is_not_case_sensitive_but_must_be_right(): void
    {
        $this->post(route('student.login'), ['student_id' => 'ADM-001', 'surname' => 'okoro', 'pin' => $this->pin])->assertRedirect(route('portal.dashboard'));
        $this->post(route('logout'));
        $this->flushSession();

        $this->post(route('student.login'), ['student_id' => 'ADM-001', 'surname' => 'ADEYEMI', 'pin' => $this->pin])->assertSessionHasErrors('student_id');
        $this->assertGuest();
    }

    #[Test]
    public function repeated_wrong_attempts_lock_the_sign_in_for_a_while(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('student.login'), ['student_id' => 'ADM-001', 'surname' => 'WRONG'.$i]);
        }
        $this->post(route('student.login'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])->assertSessionHasErrors('student_id');
        $this->assertGuest();
    }

    #[Test]
    public function students_cannot_sign_in_when_the_student_portal_is_switched_off(): void
    {
        $this->settings(['student_portal_enabled' => false]);
        $this->post(route('student.login'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])->assertSessionHasErrors('student_id');
        $this->assertGuest();
    }

    #[Test]
    public function a_student_record_linked_to_a_staff_account_is_refused(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        $user->assignRole('Teacher');
        $this->student->forceFill(['user_id' => $user->id])->save();

        $this->post(route('student.login'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])->assertSessionHasErrors('student_id');
        $this->assertGuest();
    }

    #[Test]
    public function parents_are_turned_away_when_the_parent_portal_is_switched_off(): void
    {
        $parent = User::factory()->create(['password' => Hash::make('Parent-Passw0rd!x')]);
        $parent->assignRole('Parent');
        ParentProfile::query()->create(['user_id' => $parent->id, 'full_name' => 'Mr Okoro']);
        $this->settings(['parent_portal_enabled' => false]);

        $this->post(route('login.submit'), ['email' => $parent->email, 'password' => 'Parent-Passw0rd!x'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function an_open_session_is_closed_when_the_portal_is_switched_off(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        $this->student->forceFill(['user_id' => $user->id])->save();
        $this->actingAs($user)->get(route('portal.dashboard'))->assertOk();

        $this->settings(['student_portal_enabled' => false]);
        $this->actingAs($user)->get(route('portal.dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function staff_can_still_sign_in_when_portals_are_off(): void
    {
        $this->settings(['student_portal_enabled' => false, 'parent_portal_enabled' => false]);
        $teacher = User::factory()->create(['password' => Hash::make('Teacher-Passw0rd!x')]);
        $teacher->assignRole('Teacher');

        $this->post(route('login.submit'), ['email' => $teacher->email, 'password' => 'Teacher-Passw0rd!x'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($teacher);
    }

    #[Test]
    public function the_result_checker_is_unavailable_until_the_school_switches_it_on(): void
    {
        $this->get(route('result-check'))->assertOk()->assertSee('not available right now');
        $this->post(route('result-check.lookup'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])->assertNotFound();
    }

    #[Test]
    public function the_result_checker_shows_published_results_through_expiring_signed_links(): void
    {
        $this->settings(['public_result_check_enabled' => true]);

        $this->post(route('result-check.lookup'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])
            ->assertOk()->assertSee('Ada Okoro')->assertSee('First Term');

        // The report link only works when it carries a valid signature.
        $this->get(route('result-check.report', ['student' => $this->student->id, 'term' => $this->term->id]))->assertForbidden();

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('result-check.report', now()->addMinutes(5), ['student' => $this->student->id, 'term' => $this->term->id]);
        $this->get($url)->assertOk()->assertSee('Okoro');
    }

    #[Test]
    public function the_public_checker_lists_only_the_current_published_term(): void
    {
        $historicalTerm = AcademicTerm::query()->create([
            'academic_session_id' => $this->term->academic_session_id,
            'name' => 'Second Term',
            'sequence' => 2,
            'is_current' => false,
        ]);
        $this->publishResultFor($historicalTerm);
        $this->settings(['public_result_check_enabled' => true]);

        $this->post(route('result-check.lookup'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])
            ->assertOk()
            ->assertSee('First Term')
            ->assertDontSee('Second Term');
    }

    #[Test]
    public function a_signed_public_report_link_for_a_non_current_term_is_rejected(): void
    {
        $historicalTerm = AcademicTerm::query()->create([
            'academic_session_id' => $this->term->academic_session_id,
            'name' => 'Second Term',
            'sequence' => 2,
            'is_current' => false,
        ]);
        $this->publishResultFor($historicalTerm);
        $this->settings(['public_result_check_enabled' => true]);
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'result-check.report',
            now()->addMinutes(5),
            ['student' => $this->student->id, 'term' => $historicalTerm->id],
        );

        $this->get($url)->assertNotFound();
    }

    #[Test]
    public function the_result_checker_rejects_wrong_surnames_and_students_without_published_results(): void
    {
        $this->settings(['public_result_check_enabled' => true]);

        $this->post(route('result-check.lookup'), ['student_id' => 'ADM-001', 'surname' => 'WRONG', 'pin' => $this->pin])->assertSessionHasErrors('student_id');

        Result::query()->update(['status' => 'pending', 'published_at' => null]);
        $this->post(route('result-check.lookup'), ['student_id' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $this->pin])->assertSessionHasErrors('student_id');
    }
}
