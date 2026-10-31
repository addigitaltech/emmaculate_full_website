<?php

namespace App\Http\Controllers;

use App\Domain\Payments\FeePaymentPlanner;
use App\Domain\Payments\PaymentGatewayException;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Payments\PaymentSettlementService;
use App\Models\PaymentAllocation;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    public function checkout(Request $request, StudentFeeAssignment $assignment, string $gateway, PaymentGatewayManager $gateways, FeePaymentPlanner $planner): RedirectResponse
    {
        // Never trust the browser: only a safe idempotency key and a plain amount are accepted, everything else is
        // worked out from the database.
        $input = $request->validate([
            'idempotency_key' => ['nullable', 'string', 'regex:/^[A-Za-z0-9\-_]{8,100}$/'],
            'amount' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
        ], ['amount.regex' => 'Enter the amount in naira, for example 15000 or 15000.50.']);
        $user = $request->user();
        $assignment->load('student');
        abort_unless($assignment->student && $this->mayPayFor($user, $assignment->student), 403);
        abort_unless($assignment->student->status === 'active' || $this->mayPayForInactive($user), 403, 'Online payment is not available for this student.');

        $config = PaymentGatewayConfig::query()->where('driver', $gateway)->firstOrFail();
        abort_unless($config->is_enabled && $config->supports_online_checkout, 404, 'This payment option is not available.');
        $provider = $gateways->resolve($gateway);
        abort_unless($provider->supportsOnlineCheckout() && $this->isConfigured($gateway), 503, 'This online payment option has not been configured by the school.');

        $plan = $planner->plan($assignment, $input['amount'] ?? null);
        $key = $input['idempotency_key'] ?? (string) Str::uuid();

        $existing = PaymentTransaction::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            abort_unless((int) $existing->initiated_by === (int) $user->id && (int) $existing->student_id === (int) $assignment->student_id, 409);
            if ($existing->checkout_url && in_array($existing->status, ['pending', 'processing'], true)) {
                return redirect()->away($existing->checkout_url);
            }
            abort(409, 'This payment attempt has already been used. Refresh the fee page to start a new attempt.');
        }
        // One payment at a time per student, so the same balance cannot be paid twice by accident.
        abort_if(
            PaymentTransaction::query()->where('student_id', $assignment->student_id)->whereIn('status', ['pending', 'processing'])->where('created_at', '>', now()->subMinutes(10))->exists(),
            409,
            'A payment for this student is already in progress. Please finish it, or wait ten minutes and try again.',
        );

        $single = count($plan['allocations']) === 1 ? $plan['allocations'][0]['fee'] : null;
        $transaction = DB::transaction(function () use ($plan, $assignment, $user, $gateway, $key, $single): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'fee_assignment_id' => $single?->id,
                'student_id' => $assignment->student_id,
                'initiated_by' => $user->id,
                'gateway' => $gateway,
                'reference' => 'EAP-'.Str::upper(Str::random(18)),
                'idempotency_key' => $key,
                'amount' => $plan['total'],
                'currency' => 'NGN',
                'status' => 'pending',
                'safe_metadata' => ['mode' => $plan['mode'], 'fee_assignment_ids' => collect($plan['allocations'])->map(fn ($row) => $row['fee']->id)->all()],
            ]);
            foreach ($plan['allocations'] as $row) {
                PaymentAllocation::query()->create(['payment_transaction_id' => $transaction->id, 'fee_assignment_id' => $row['fee']->id, 'amount' => $row['amount']]);
            }

            return $transaction;
        });

        try {
            $session = $provider->initiate($transaction, $this->customerEmail($user, $assignment->student), $this->safeName((string) $user->name));
            $checkoutUrl = (string) ($session['authorization_url'] ?? '');
            abort_unless($this->approvedCheckoutUrl($gateway, $checkoutUrl), 502, 'The payment provider returned an untrusted checkout address.');
            $transaction->update([
                'checkout_url' => $checkoutUrl,
                'provider_reference' => $session['provider_reference'] ?? null,
                'attempt_count' => $transaction->attempt_count + 1,
            ]);
            return redirect()->away($checkoutUrl);
        } catch (PaymentGatewayException $exception) {
            report($exception);
            return back()->withErrors(['payment' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['payment' => 'The payment provider could not start checkout. No payment has been confirmed. Please try again later.']);
        }
    }

    /** The name sent to the payment provider: plain text only, no markup or control characters. */
    private function safeName(string $name): string
    {
        $clean = trim(preg_replace('/[^\p{L}\p{N} .\'\-]+/u', '', strip_tags($name)) ?? '');

        return $clean !== '' ? mb_substr($clean, 0, 80) : 'School fee payer';
    }

    private function mayPayForInactive($user): bool
    {
        // Parents (and finance staff) can still settle fees of a student who has since graduated.
        return $user->hasRole('Super Admin') || $user->can('manage payments') || $user->hasRole('Parent');
    }

    public function providerReturn(Request $request, PaymentGatewayManager $gateways, PaymentSettlementService $settlement): RedirectResponse
    {
        $reference = $request->query('reference') ?: $request->query('trxref') ?: $request->query('tx_ref');
        if (! is_string($reference) || $reference === '' || strlen($reference) > 100) {
            return redirect()->route('login')->with('status', 'Return to the school portal to review your payment status.');
        }
        $transaction = PaymentTransaction::query()->where('reference', $reference)->first();
        if (! $transaction) {
            return redirect()->route('login')->with('status', 'Return to the school portal to review your payment status.');
        }
        if ($transaction->status === 'successful') {
            return redirect()->route('portal.dashboard')->with('success', 'Your verified payment has been recorded.');
        }
        try {
            $provider = $gateways->resolve($transaction->gateway);
            $providerReference = $request->query('transaction_id') ?: $request->query('id');
            $verification = $provider->verify($transaction, is_string($providerReference) ? $providerReference : null);
            if (($verification['verified'] ?? false) === true) {
                $settlement->settleVerified($transaction, (string) ($verification['provider_reference'] ?? ''), $request->user());
                return redirect()->route('portal.dashboard')->with('success', 'Your payment was verified and recorded.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
        return redirect()->route($request->user() ? 'portal.dashboard' : 'login')->with('status', 'The provider return did not confirm payment. The fee remains pending until a verified provider update is received.');
    }

    /** Payment gateways need a real email for the receipt. Student accounts have none, so use a parent's, then the school's. */
    private function customerEmail($user, Student $student): string
    {
        $email = (string) $user->email;
        if ($email !== '' && ! str_ends_with(strtolower($email), '.invalid') && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        $parentEmail = $student->parents()->whereNotNull('email')->value('email');
        if (filled($parentEmail)) {
            return (string) $parentEmail;
        }

        return (string) (\App\Models\SchoolSettings::current()->email ?: config('mail.from.address'));
    }

    private function mayPayFor($user, Student $student): bool
    {
        if ($user->hasRole('Super Admin') || $user->can('manage payments')) {
            return true;
        }
        if ($user->hasRole('Student')) {
            return (int) $user->studentProfile()->value('id') === (int) $student->id;
        }
        if ($user->hasRole('Parent')) {
            $parent = $user->parentProfile()->first();
            return $parent?->students()->whereKey($student->id)->exists() ?? false;
        }
        return false;
    }

    private function isConfigured(string $driver): bool
    {
        return match ($driver) {
            'paystack' => filled(config('services.paystack.secret_key')),
            'flutterwave' => filled(config('services.flutterwave.secret_key')) && filled(config('services.flutterwave.secret_hash')),
            default => false,
        };
    }

    private function approvedCheckoutUrl(string $driver, string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($scheme !== 'https' || $host === '' || parse_url($url, PHP_URL_USER) !== null) {
            return false;
        }
        return match ($driver) {
            'paystack' => $host === 'checkout.paystack.com' || str_ends_with($host, '.paystack.com'),
            'flutterwave' => $host === 'checkout.flutterwave.com' || str_ends_with($host, '.flutterwave.com'),
            default => false,
        };
    }
}
