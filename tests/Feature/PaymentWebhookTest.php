<?php

namespace Tests\Feature;

use App\Models\FeeType;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private StudentFeeAssignment $fee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        Mail::fake();

        SchoolSettings::current()->forceFill([
            'finance_notify_emails' => 'bursar@example.test',
            'finance_notify_phones' => '',
        ])->save();

        $class = SchoolClass::query()->create(['name' => 'JSS1', 'is_active' => true]);
        $this->student = Student::query()->create([
            'first_name' => 'Ada',
            'last_name' => 'Okoro',
            'school_class_id' => $class->id,
            'status' => 'active',
        ]);
        $feeType = FeeType::query()->create(['name' => 'Tuition', 'is_active' => true]);
        $this->fee = StudentFeeAssignment::query()->create([
            'student_id' => $this->student->id,
            'fee_type_id' => $feeType->id,
            'amount_due' => 10000,
            'currency' => 'NGN',
            'status' => 'unpaid',
        ]);
    }

    #[Test]
    public function paystack_accepts_a_valid_signature_verifies_and_settles_the_payment(): void
    {
        config([
            'services.paystack.secret_key' => 'paystack-test-secret',
            'services.paystack.base_url' => 'https://api.paystack.test',
        ]);

        $transaction = $this->payment('paystack', 'PSK-VALID-001');
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'id' => 12345,
                'reference' => $transaction->reference,
                'status' => 'success',
                'amount' => 1000000,
                'currency' => 'NGN',
            ],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $payload, 'paystack-test-secret');

        Http::fake([
            'https://api.paystack.test/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'reference' => $transaction->reference,
                    'status' => 'success',
                    'amount' => 1000000,
                    'currency' => 'NGN',
                ],
            ]),
        ]);

        $response = $this->sendWebhook('paystack', $payload, ['x-paystack-signature' => $signature]);

        $response->assertOk()->assertJson(['received' => true]);
        $this->assertSame('successful', $transaction->fresh()->status);
        $this->assertSame('paid', $this->fee->fresh()->status);
        $this->assertDatabaseHas('payment_events', [
            'gateway' => 'paystack',
            'event_type' => 'charge.success',
            'processing_status' => 'processed',
            'payment_transaction_id' => $transaction->id,
        ]);
        Http::assertSentCount(1);
    }

    #[Test]
    public function paystack_rejects_an_invalid_signature_without_verifying_or_settling(): void
    {
        config(['services.paystack.secret_key' => 'paystack-test-secret']);
        $transaction = $this->payment('paystack', 'PSK-INVALID-001');
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['id' => 12346, 'reference' => $transaction->reference],
        ], JSON_THROW_ON_ERROR);
        Http::fake();

        $response = $this->sendWebhook('paystack', $payload, ['x-paystack-signature' => 'not-valid']);

        $response->assertUnauthorized()->assertJson(['message' => 'Invalid webhook signature.']);
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame('unpaid', $this->fee->fresh()->status);
        $this->assertDatabaseCount('payment_events', 0);
        Http::assertNothingSent();
    }

    #[Test]
    public function paystack_processes_the_same_webhook_only_once(): void
    {
        config([
            'services.paystack.secret_key' => 'paystack-test-secret',
            'services.paystack.base_url' => 'https://api.paystack.test',
        ]);

        $transaction = $this->payment('paystack', 'PSK-DUPLICATE-001');
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'id' => 12347,
                'reference' => $transaction->reference,
                'status' => 'success',
                'amount' => 1000000,
                'currency' => 'NGN',
            ],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $payload, 'paystack-test-secret');
        Http::fake([
            'https://api.paystack.test/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'reference' => $transaction->reference,
                    'status' => 'success',
                    'amount' => 1000000,
                    'currency' => 'NGN',
                ],
            ]),
        ]);

        $this->sendWebhook('paystack', $payload, ['x-paystack-signature' => $signature])->assertOk();
        $this->sendWebhook('paystack', $payload, ['x-paystack-signature' => $signature])->assertOk();

        $this->assertSame(1, PaymentEvent::query()->where('gateway', 'paystack')->count());
        $this->assertSame('successful', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->receipt);
        Http::assertSentCount(1);
    }

    #[Test]
    public function flutterwave_accepts_a_valid_hash_verifies_and_settles_the_payment(): void
    {
        config([
            'services.flutterwave.secret_key' => 'flutterwave-test-secret',
            'services.flutterwave.secret_hash' => 'flutterwave-webhook-hash',
            'services.flutterwave.base_url' => 'https://api.flutterwave.test/v3',
        ]);

        $transaction = $this->payment('flutterwave', 'FLW-VALID-001');
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => [
                'id' => 98765,
                'tx_ref' => $transaction->reference,
                'status' => 'successful',
                'amount' => 10000,
                'currency' => 'NGN',
            ],
        ], JSON_THROW_ON_ERROR);
        Http::fake([
            'https://api.flutterwave.test/v3/transactions/98765/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 98765,
                    'tx_ref' => $transaction->reference,
                    'status' => 'successful',
                    'amount' => 10000,
                    'currency' => 'NGN',
                ],
            ]),
        ]);

        $response = $this->sendWebhook('flutterwave', $payload, ['verif-hash' => 'flutterwave-webhook-hash']);

        $response->assertOk()->assertJson(['received' => true]);
        $this->assertSame('successful', $transaction->fresh()->status);
        $this->assertSame('paid', $this->fee->fresh()->status);
        $this->assertDatabaseHas('payment_events', [
            'gateway' => 'flutterwave',
            'event_type' => 'charge.completed',
            'processing_status' => 'processed',
            'payment_transaction_id' => $transaction->id,
        ]);
        Http::assertSentCount(1);
    }

    #[Test]
    public function flutterwave_rejects_an_invalid_hash_without_verifying_or_settling(): void
    {
        config([
            'services.flutterwave.secret_key' => 'flutterwave-test-secret',
            'services.flutterwave.secret_hash' => 'flutterwave-webhook-hash',
        ]);
        $transaction = $this->payment('flutterwave', 'FLW-INVALID-001');
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => ['id' => 98766, 'tx_ref' => $transaction->reference, 'status' => 'successful'],
        ], JSON_THROW_ON_ERROR);
        Http::fake();

        $response = $this->sendWebhook('flutterwave', $payload, ['verif-hash' => 'not-valid']);

        $response->assertUnauthorized()->assertJson(['message' => 'Invalid webhook signature.']);
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame('unpaid', $this->fee->fresh()->status);
        $this->assertDatabaseCount('payment_events', 0);
        Http::assertNothingSent();
    }

    #[Test]
    public function flutterwave_processes_the_same_webhook_only_once(): void
    {
        config([
            'services.flutterwave.secret_key' => 'flutterwave-test-secret',
            'services.flutterwave.secret_hash' => 'flutterwave-webhook-hash',
            'services.flutterwave.base_url' => 'https://api.flutterwave.test/v3',
        ]);

        $transaction = $this->payment('flutterwave', 'FLW-DUPLICATE-001');
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => [
                'id' => 98767,
                'tx_ref' => $transaction->reference,
                'status' => 'successful',
                'amount' => 10000,
                'currency' => 'NGN',
            ],
        ], JSON_THROW_ON_ERROR);
        Http::fake([
            'https://api.flutterwave.test/v3/transactions/98767/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 98767,
                    'tx_ref' => $transaction->reference,
                    'status' => 'successful',
                    'amount' => 10000,
                    'currency' => 'NGN',
                ],
            ]),
        ]);

        $this->sendWebhook('flutterwave', $payload, ['verif-hash' => 'flutterwave-webhook-hash'])->assertOk();
        $this->sendWebhook('flutterwave', $payload, ['verif-hash' => 'flutterwave-webhook-hash'])->assertOk();

        $this->assertSame(1, PaymentEvent::query()->where('gateway', 'flutterwave')->count());
        $this->assertSame('successful', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->receipt);
        Http::assertSentCount(1);
    }

    private function payment(string $gateway, string $reference): PaymentTransaction
    {
        return PaymentTransaction::query()->create([
            'fee_assignment_id' => $this->fee->id,
            'student_id' => $this->student->id,
            'gateway' => $gateway,
            'reference' => $reference,
            'idempotency_key' => strtolower($reference),
            'amount' => 10000,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);
    }

    private function sendWebhook(string $gateway, string $payload, array $headers): \Illuminate\Testing\TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call('POST', route('payments.webhook', ['gateway' => $gateway]), [], [], [], $server, $payload);
    }
}
