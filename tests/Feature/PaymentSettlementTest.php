<?php

namespace Tests\Feature;

use App\Domain\Payments\PaymentSettlementService;
use App\Models\FeeType;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function verified_settlement_is_idempotent_and_refund_reopens_the_fee(): void
    {
        $student = Student::query()->create(['student_number' => 'EA-PAY-001', 'first_name' => 'Paying', 'last_name' => 'Student', 'status' => 'active']);
        $feeType = FeeType::query()->create(['name' => 'Tuition '.uniqid(), 'is_active' => true]);
        $assignment = StudentFeeAssignment::query()->create([
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'amount_due' => 25000,
            'currency' => 'NGN',
            'status' => 'unpaid',
        ]);
        $transaction = PaymentTransaction::query()->create([
            'fee_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'gateway' => 'paystack',
            'reference' => 'EAP-TEST-'.strtoupper(bin2hex(random_bytes(5))),
            'idempotency_key' => 'test-'.bin2hex(random_bytes(8)),
            'amount' => 25000,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);
        $settlement = app(PaymentSettlementService::class);

        self::assertTrue($settlement->settleVerified($transaction, 'provider-123'));
        self::assertTrue($settlement->settleVerified($transaction->fresh(), 'provider-123'));
        self::assertSame('successful', $transaction->fresh()->status);
        self::assertSame('provider-123', $transaction->fresh()->provider_reference);
        self::assertSame('paid', $assignment->fresh()->status);
        self::assertDatabaseCount('payment_receipts', 1);

        self::assertTrue($settlement->markRefunded($transaction->fresh()));
        self::assertSame('refunded', $transaction->fresh()->status);
        self::assertSame('unpaid', $assignment->fresh()->status);
        self::assertDatabaseCount('payment_receipts', 1);
    }
}
