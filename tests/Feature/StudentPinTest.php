<?php

namespace Tests\Feature;

use App\Domain\Results\Services\StudentPinService;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\FeeType;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Student PIN sign-in: PINs are hashed, wrong guesses lock the student whatever the IP address. */
class StudentPinTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private AcademicTerm $term;
    private StudentPinService $pins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        RateLimiter::clear('student-login:'.hash('sha256', 'ADM-001|127.0.0.1'));

        $session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $this->term = AcademicTerm::query()->create(['academic_session_id' => $session->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => true]);
        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $subject = Subject::query()->create(['name' => 'Mathematics', 'status' => 'active']);
        $this->student = Student::query()->create(['student_number' => 'ADM-001', 'first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $class->id, 'status' => 'active']);
        Result::query()->create([
            'student_id' => $this->student->id, 'subject_id' => $subject->id, 'school_class_id' => $class->id,
            'academic_session_id' => $session->id, 'academic_term_id' => $this->term->id,
            'ca1_score' => 8, 'ca2_score' => 0, 'ca3_score' => 0, 'exam_score' => 60, 'total_score' => 68,
            'is_offered' => true, 'grade' => 'B', 'remark' => 'Very Good', 'status' => 'published', 'published_at' => now(),
        ]);
        $this->pins = app(StudentPinService::class);
        // The PIN is a system setting (off by default); these tests exercise it switched on.
        SchoolSettings::current()->forceFill(['require_pin_student_login' => true, 'require_pin_result_check' => true])->save();
    }

    #[Test]
    public function an_issued_pin_is_six_digits_and_only_its_hash_is_stored(): void
    {
        $pin = $this->pins->issue($this->student);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $pin);
        $row = Student::query()->find($this->student->id);
        $this->assertNotSame($pin, $row->pin_hash);
        $this->assertTrue(Hash::check($pin, $row->pin_hash));
        $this->assertNotNull($row->pin_set_at);
    }

    #[Test]
    public function signing_in_needs_the_correct_pin(): void
    {
        $pin = $this->pins->issue($this->student);
        $wrong = $pin === '000000' ? '111111' : '000000';

        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $wrong])->assertSessionHasErrors('admission_number');
        $this->assertGuest();

        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $pin])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function signing_in_without_a_pin_field_is_rejected(): void
    {
        $this->pins->issue($this->student);

        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO'])->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    #[Test]
    public function a_student_with_no_pin_cannot_sign_in_and_is_told_to_ask_the_office(): void
    {
        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => '123456'])->assertSessionHasErrors('admission_number');
        $this->assertGuest();
    }

    #[Test]
    public function wrong_pins_lock_the_student_even_when_the_guesses_come_from_different_addresses(): void
    {
        $pin = $this->pins->issue($this->student);
        $wrong = $pin === '000000' ? '111111' : '000000';

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4', '10.0.0.5'] as $address) {
            $this->withServerVariables(['REMOTE_ADDR' => $address])
                ->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $wrong]);
        }

        // Even the correct PIN from a brand-new address is refused while the student is locked.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $pin])
            ->assertSessionHasErrors('admission_number');
        $this->assertGuest();

        // The lock ends after 15 minutes.
        $this->travel(16)->minutes();
        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $pin])->assertRedirect(route('portal.dashboard'));
    }

    #[Test]
    public function issuing_a_new_pin_replaces_the_old_one_and_clears_a_lock(): void
    {
        $old = $this->pins->issue($this->student);
        $wrong = $old === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->pins->attempt($this->student, $wrong);
        }
        $this->assertSame('locked', $this->pins->attempt($this->student, $old));

        $new = $this->pins->issue($this->student);

        $this->assertSame('ok', $this->pins->attempt($this->student, $new));
        if ($new !== $old) {
            $this->assertSame('wrong', $this->pins->attempt($this->student, $old));
        }
    }

    #[Test]
    public function repeated_locks_get_longer(): void
    {
        $pin = $this->pins->issue($this->student);
        $wrong = $pin === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->pins->attempt($this->student, $wrong);
        }
        $first = Student::query()->find($this->student->id)->pin_locked_until;

        $this->travel(16)->minutes();
        for ($i = 0; $i < 5; $i++) {
            $this->pins->attempt($this->student, $wrong);
        }
        $second = Student::query()->find($this->student->id)->pin_locked_until;

        $this->assertGreaterThan(20, now()->diffInMinutes($second, false));
        $this->assertNotNull($first);
    }

    #[Test]
    public function the_public_result_checker_also_needs_the_pin(): void
    {
        SchoolSettings::current()->forceFill(['public_result_check_enabled' => true])->save();
        $pin = $this->pins->issue($this->student);
        $wrong = $pin === '000000' ? '111111' : '000000';

        $this->post(route('result-check.lookup'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $wrong])->assertSessionHasErrors('admission_number');
        $this->post(route('result-check.lookup'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => $pin])->assertOk();
    }

    #[Test]
    public function administrators_see_whose_published_reports_are_withheld_for_unpaid_fees(): void
    {
        SchoolSettings::current()->forceFill(['payment_gate_results' => true])->save();
        $feeType = FeeType::query()->create(['name' => 'Tuition', 'is_active' => true]);
        StudentFeeAssignment::query()->create(['student_id' => $this->student->id, 'fee_type_id' => $feeType->id, 'amount_due' => 25000, 'currency' => 'NGN', 'status' => 'unpaid']);
        $admin = User::factory()->create();
        $admin->assignRole('Results Admin');

        $this->actingAs($admin)->get(route('staff.results', ['term' => $this->term->id]))
            ->assertOk()
            ->assertSee('Reports withheld for unpaid fees')
            ->assertSee('OKORO')
            ->assertSee('25,000.00');
    }

    #[Test]
    public function the_withheld_list_is_hidden_when_the_fee_gate_is_off(): void
    {
        SchoolSettings::current()->forceFill(['payment_gate_results' => false])->save();
        $admin = User::factory()->create();
        $admin->assignRole('Results Admin');

        $this->actingAs($admin)->get(route('staff.results', ['term' => $this->term->id]))
            ->assertOk()
            ->assertDontSee('Reports withheld for unpaid fees');
    }

    #[Test]
    public function the_pin_field_is_shown_and_asked_for_only_while_the_student_login_switch_is_on(): void
    {
        $this->get(route('login.student'))->assertOk()->assertSee('PIN (6 digits');

        SchoolSettings::current()->forceFill(['require_pin_student_login' => false])->save();

        $this->get(route('login.student'))->assertOk()->assertDontSee('PIN (6 digits');
    }

    #[Test]
    public function with_the_student_pin_switch_off_the_admission_number_and_surname_are_enough(): void
    {
        SchoolSettings::current()->forceFill(['require_pin_student_login' => false])->save();

        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function with_the_result_check_pin_switch_off_the_checker_needs_no_pin(): void
    {
        SchoolSettings::current()->forceFill(['public_result_check_enabled' => true, 'require_pin_result_check' => false])->save();

        $this->get(route('result-check'))->assertOk()->assertDontSee('PIN (6 digits');
        $this->post(route('result-check.lookup'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO'])->assertOk()->assertSee('Ada Okoro');
    }

    #[Test]
    public function the_two_pin_switches_work_independently(): void
    {
        // Result checker needs a PIN, student sign-in does not.
        SchoolSettings::current()->forceFill(['public_result_check_enabled' => true, 'require_pin_result_check' => true, 'require_pin_student_login' => false])->save();

        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO'])->assertRedirect(route('portal.dashboard'));
        $this->post(route('result-check.lookup'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO'])->assertSessionHasErrors('pin');
    }

    #[Test]
    public function a_wrong_pin_is_ignored_while_the_pin_switch_is_off(): void
    {
        $this->pins->issue($this->student);
        SchoolSettings::current()->forceFill(['require_pin_student_login' => false])->save();

        $this->post(route('student.login'), ['admission_number' => 'ADM-001', 'surname' => 'OKORO', 'pin' => '999999'])->assertRedirect(route('portal.dashboard'));
        $this->assertSame(0, (int) Student::query()->find($this->student->id)->pin_failed_attempts);
    }
}
