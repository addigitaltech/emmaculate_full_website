<?php

namespace Tests\Feature;

use App\Domain\Payments\FeeBalance;
use App\Domain\Payments\FeePaymentPlanner;
use App\Domain\Payments\PaymentSettlementService;
use App\Filament\Pages\AssignFees;
use App\Http\Controllers\FinanceReportController;
use App\Mail\PaymentAlertMail;
use App\Mail\PaymentReceivedMail;
use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\FeeType;
use App\Models\ParentProfile;
use App\Models\PaymentAllocation;
use App\Models\PaymentTransaction;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Installments or full payment, debts in the portals, notifications, receipts and the payment history. */
class PaymentsV2Test extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private FeeType $tuition;
    private FeeType $uniform;
    private StudentFeeAssignment $tuitionFee;
    private StudentFeeAssignment $uniformFee;
    private User $parentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        SchoolSettings::current()->forceFill(['payment_mode' => 'installment', 'installment_min_amount' => 1000, 'finance_notify_emails' => 'bursar@example.test', 'finance_notify_phones' => '08031234567'])->save();

        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $this->student = Student::query()->create(['first_name' => 'Ada', 'last_name' => 'Okoro', 'school_class_id' => $class->id, 'status' => 'active']);
        $this->tuition = FeeType::query()->create(['name' => 'Tuition', 'is_active' => true]);
        $this->uniform = FeeType::query()->create(['name' => 'Uniform', 'is_active' => true]);
        $this->tuitionFee = $this->fee($this->tuition, 25000);
        $this->uniformFee = $this->fee($this->uniform, 5000);

        $this->parentUser = User::factory()->create(['email' => 'parent@example.test']);
        $this->parentUser->assignRole('Parent');
        $profile = ParentProfile::query()->create(['user_id' => $this->parentUser->id, 'full_name' => 'Mrs Okoro', 'email' => 'parent@example.test', 'phone' => '08098765432']);
        $profile->students()->attach($this->student->id);
    }

    private function fee(FeeType $type, int $amount): StudentFeeAssignment
    {
        return StudentFeeAssignment::query()->create(['student_id' => $this->student->id, 'fee_type_id' => $type->id, 'amount_due' => $amount, 'currency' => 'NGN', 'status' => 'unpaid']);
    }

    private function payment(array $allocations, ?User $payer = null, string $status = 'pending'): PaymentTransaction
    {
        $transaction = PaymentTransaction::query()->create([
            'fee_assignment_id' => count($allocations) === 1 ? array_key_first($allocations) : null,
            'student_id' => $this->student->id,
            'initiated_by' => ($payer ?? $this->parentUser)->id,
            'gateway' => 'paystack',
            'reference' => 'EAP-TEST-'.strtoupper(bin2hex(random_bytes(5))),
            'idempotency_key' => 'test-'.bin2hex(random_bytes(8)),
            'amount' => array_sum($allocations),
            'currency' => 'NGN',
            'status' => $status,
        ]);
        foreach ($allocations as $feeId => $amount) {
            PaymentAllocation::query()->create(['payment_transaction_id' => $transaction->id, 'fee_assignment_id' => $feeId, 'amount' => $amount]);
        }

        return $transaction;
    }

    #[Test]
    public function the_planner_accepts_a_sensible_installment_and_refuses_everything_else(): void
    {
        $planner = app(FeePaymentPlanner::class);

        $plan = $planner->plan($this->tuitionFee, '5000');
        $this->assertSame('5000.00', $plan['total']);
        $this->assertSame('installment', $plan['mode']);

        foreach (['500', '25000.01', 'abc', '-5', '10.999', '1e5', '', ' '] as $bad) {
            try {
                $result = $planner->plan($this->tuitionFee, $bad);
                // an empty amount means "the whole balance"
                $this->assertContains($bad, ['', ' '], 'amount "'.$bad.'" should have been refused');
                $this->assertSame('25000.00', $result['total']);
            } catch (ValidationException) {
                $this->assertNotContains($bad, ['']);
            }
        }
    }

    #[Test]
    public function the_balance_can_always_be_paid_even_when_it_is_below_the_minimum(): void
    {
        $this->tuitionFee->forceFill(['amount_paid' => 24500, 'status' => 'partial'])->save();

        $plan = app(FeePaymentPlanner::class)->plan($this->tuitionFee->fresh(), '500');

        $this->assertSame('500.00', $plan['total']);
    }

    #[Test]
    public function in_full_payment_mode_every_unpaid_fee_is_added_together(): void
    {
        SchoolSettings::current()->forceFill(['payment_mode' => 'full'])->save();

        $plan = app(FeePaymentPlanner::class)->plan($this->uniformFee, '1');

        $this->assertSame('full', $plan['mode']);
        $this->assertSame('30000.00', $plan['total']);
        $this->assertCount(2, $plan['allocations']);
    }

    #[Test]
    public function installments_move_a_fee_from_unpaid_to_part_paid_to_paid_and_refunds_move_it_back(): void
    {
        Mail::fake();
        $settlement = app(PaymentSettlementService::class);
        $first = $this->payment([$this->tuitionFee->id => 10000]);
        $second = $this->payment([$this->tuitionFee->id => 15000]);

        $this->assertTrue($settlement->settleVerified($first, 'p-1'));
        $this->assertSame('partial', $this->tuitionFee->fresh()->status);
        $this->assertSame('10000.00', (string) $this->tuitionFee->fresh()->amount_paid);
        $this->assertEquals(15000.0, $this->tuitionFee->fresh()->balance());

        $this->assertTrue($settlement->settleVerified($second, 'p-2'));
        $this->assertSame('paid', $this->tuitionFee->fresh()->status);
        $this->assertDatabaseCount('payment_receipts', 2);

        $this->assertTrue($settlement->markRefunded($first->fresh()));
        $this->assertSame('partial', $this->tuitionFee->fresh()->status);
        $this->assertEquals(15000.0, (float) $this->tuitionFee->fresh()->amount_paid);
    }

    #[Test]
    public function a_full_payment_settles_every_fee_it_covers(): void
    {
        Mail::fake();
        $payment = $this->payment([$this->tuitionFee->id => 25000, $this->uniformFee->id => 5000]);

        app(PaymentSettlementService::class)->settleVerified($payment, 'p-full');

        $this->assertSame('paid', $this->tuitionFee->fresh()->status);
        $this->assertSame('paid', $this->uniformFee->fresh()->status);
        $this->assertSame(0.0, FeeBalance::forStudent($this->student->id)['balance']);
    }

    #[Test]
    public function the_school_and_the_payer_are_told_once_by_email_and_sms(): void
    {
        Mail::fake();
        config(['services.sms.driver' => 'termii', 'services.sms.termii.api_key' => 'key', 'services.sms.termii.sender_id' => 'SCHOOL']);
        Http::fake(['api.ng.termii.com/*' => Http::response(['code' => 'ok'], 200)]);
        $settlement = app(PaymentSettlementService::class);
        $payment = $this->payment([$this->tuitionFee->id => 10000]);

        $settlement->settleVerified($payment, 'p-1');
        $settlement->settleVerified($payment->fresh(), 'p-1'); // the provider calls twice: nobody is told twice

        Mail::assertSent(PaymentReceivedMail::class, 1);
        Mail::assertSent(PaymentReceivedMail::class, fn ($mail) => $mail->hasTo('parent@example.test'));
        Mail::assertSent(PaymentAlertMail::class, fn ($mail) => $mail->hasTo('bursar@example.test'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === '2348031234567' && str_contains($request['sms'], '10,000.00'));
    }

    #[Test]
    public function a_failure_to_notify_never_undoes_a_verified_payment(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail server down'));
        $payment = $this->payment([$this->tuitionFee->id => 25000]);

        $this->assertTrue(app(PaymentSettlementService::class)->settleVerified($payment, 'p-1'));
        $this->assertSame('successful', $payment->fresh()->status);
        $this->assertSame('paid', $this->tuitionFee->fresh()->status);
    }

    #[Test]
    public function the_receipt_opens_for_the_payer_and_finance_but_not_for_other_parents(): void
    {
        Mail::fake();
        $payment = $this->payment([$this->tuitionFee->id => 10000]);
        app(PaymentSettlementService::class)->settleVerified($payment, 'p-1');
        $receipt = $payment->fresh()->receipt;
        $url = route('receipts.show', ['receipt' => $receipt->receipt_number]);

        $this->actingAs($this->parentUser)->get($url)->assertOk()->assertSee($receipt->receipt_number)->assertSee('Tuition')->assertSee('20,000.00');
        $this->actingAs($this->parentUser)->get($url.'?download=pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $stranger = User::factory()->create();
        $stranger->assignRole('Parent');
        $this->actingAs($stranger)->get($url)->assertForbidden();

        $finance = User::factory()->create();
        $finance->assignRole('Super Admin');
        $this->actingAs($finance)->get($url)->assertOk();
    }

    #[Test]
    public function tampered_payment_input_is_refused_before_anything_is_sent_to_a_provider(): void
    {
        Http::fake();
        $this->actingAs($this->parentUser);
        $url = route('payments.checkout', ['assignment' => $this->tuitionFee->id, 'gateway' => 'paystack']);

        $this->post($url, ['amount' => 'abc'])->assertSessionHasErrors('amount');
        $this->post($url, ['amount' => '99999999999'])->assertSessionHasErrors('amount');
        $this->post($url, ['amount' => '1000', 'idempotency_key' => '<script>alert(1)</script>'])->assertSessionHasErrors('idempotency_key');

        Http::assertNothingSent();
        $this->assertSame(0, PaymentTransaction::query()->count());
    }

    #[Test]
    public function the_parent_portal_shows_what_is_owed_and_part_payments(): void
    {
        Mail::fake();
        app(PaymentSettlementService::class)->settleVerified($this->payment([$this->tuitionFee->id => 10000]), 'p-1');

        $this->actingAs($this->parentUser)->get(route('portal'))
            ->assertOk()
            ->assertSee('Total still owing')
            ->assertSee('NGN 20,000.00')
            ->assertSee('Part paid')
            ->assertSee('Payments made');
    }

    #[Test]
    public function the_student_portal_shows_the_students_debt_too(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole('Student');
        $this->student->update(['user_id' => $studentUser->id]);

        $this->actingAs($studentUser)->get(route('portal'))->assertOk()->assertSee('Total still owing')->assertSee('NGN 30,000.00');
    }

    #[Test]
    public function the_administrator_can_charge_a_fee_to_whole_classes_and_arms_without_charging_anyone_twice(): void
    {
        $session = AcademicSession::query()->create(['name' => '2026/2027', 'is_active' => true]);
        $red = Arm::query()->create(['name' => 'Red']);
        $blue = Arm::query()->create(['name' => 'Blue']);
        $class = $this->student->schoolClass;
        $class->arms()->attach([$red->id, $blue->id]);
        $this->student->update(['arm_id' => $red->id]);
        Student::query()->create(['first_name' => 'Bola', 'last_name' => 'Ajayi', 'school_class_id' => $class->id, 'arm_id' => $blue->id, 'status' => 'active']);
        $bus = FeeType::query()->create(['name' => 'Bus', 'is_active' => true]);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $form = ['fee_type_id' => $bus->id, 'amount_due' => 12000, 'academic_session_id' => $session->id, 'class_ids' => [$class->id]];
        Livewire::test(AssignFees::class)->fillForm($form + ['arm_id' => $red->id])->call('save');
        $this->assertSame(1, StudentFeeAssignment::query()->where('fee_type_id', $bus->id)->count());

        Livewire::test(AssignFees::class)->fillForm($form)->call('save'); // whole class: the Red student is skipped
        $this->assertSame(2, StudentFeeAssignment::query()->where('fee_type_id', $bus->id)->count());
        $this->assertSame(2, StudentFeeAssignment::query()->where('fee_type_id', $bus->id)->where('amount_paid', 0)->count());
    }

    #[Test]
    public function the_payment_history_totals_filters_and_prints(): void
    {
        Mail::fake();
        $settlement = app(PaymentSettlementService::class);
        $settlement->settleVerified($this->payment([$this->tuitionFee->id => 10000]), 'p-1');
        $settlement->settleVerified($this->payment([$this->tuitionFee->id => 5000]), 'p-2');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);
        $from = now()->subDay()->toDateString();
        $to = now()->addDay()->toDateString();

        $this->get(route('finance.report', ['from' => $from, 'to' => $to]))
            ->assertOk()->assertSee('15,000.00')->assertSee('Okoro')->assertSee('Still owing');

        $this->get(route('finance.report', ['from' => now()->addYear()->toDateString(), 'to' => now()->addYear()->toDateString()]))
            ->assertOk()->assertSee('No payments match these filters.');

        $this->get(route('finance.report', ['from' => 'not-a-date']))->assertSessionHasErrors('from');
        $this->get(route('finance.report', ['status' => 'hacked']))->assertSessionHasErrors('status');
        $this->get(route('finance.report', ['from' => $from, 'to' => $to, 'download' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function spreadsheet_downloads_cannot_carry_formulas(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://evil\")", FinanceReportController::safeCell('=HYPERLINK("http://evil")'));
        $this->assertSame("'+1+1", FinanceReportController::safeCell('+1+1'));
        $this->assertSame('Ada Okoro', FinanceReportController::safeCell('Ada Okoro'));

        Mail::fake();
        app(PaymentSettlementService::class)->settleVerified($this->payment([$this->tuitionFee->id => 10000]), 'p-1');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $csv = $this->actingAs($admin)->get(route('finance.report', ['download' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Ada Okoro', $csv);
        $this->assertStringContainsString('Still owing', $csv);
    }
}
