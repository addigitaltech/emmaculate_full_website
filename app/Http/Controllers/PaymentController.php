<?php

namespace App\Http\Controllers;

use App\Domain\Payments\PaymentGatewayException;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Payments\PaymentSettlementService;
use App\Models\PaymentGatewayConfig;
use App\Models\PaymentTransaction;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    public function checkout(Request $request, StudentFeeAssignment $assignment, string $gateway, PaymentGatewayManager $gateways): RedirectResponse
    {
        $request->validate(['idempotency_key' => ['nullable', 'string', 'max:100']]);
        $user = $request->user();
        $assignment->load('student');
        abort_unless($assignment->student && $this->mayPayFor($user, $assignment->student), 403);
        abort_unless(in_array($assignment->status, ['unpaid', 'overdue'], true), 409, 'This fee is not currently payable.');
        abort_unless((float) $assignment->amount_due > 0 && strtoupper($assignment->currency) === 'NGN', 422, 'This fee amount or currency is not supported for online checkout.');
        abort_unless(! $assignment->transactions()->where('status', 'successful')->exists(), 409, 'This fee has already been paid.');

        $config = PaymentGatewayConfig::query()->where('driver', $gateway)->firstOrFail();
        abort_unless($config->is_enabled && $config->supports_online_checkout, 404, 'This payment option is not available.');
        $provider = $gateways->resolve($gateway);
        abort_unless($provider->supportsOnlineCheckout() && $this->isConfigured($gateway), 503, 'This online payment option has not been configured by the school.');

        $key = $request->input('idempotency_key') ?: (string) Str::uuid();
        $existing = PaymentTransaction::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            abort_unless((int) $existing->initiated_by === (int) $user->id && (int) $existing->fee_assignment_id === (int) $assignment->id, 409);
            if ($existing->checkout_url && in_array($existing->status, ['pending', 'processing'], true)) {
                return redirect()->away($existing->checkout_url);
            }
            abort(409, 'This payment attempt has already been used. Refresh the fee page to start a new attempt.');
        }
        $transaction = PaymentTransaction::query()->create([
            'fee_assignment_id' => $assignment->id,
            'student_id' => $assignment->student_id,
            'initiated_by' => $user->id,
            'gateway' => $gateway,
            'reference' => 'EAP-'.Str::upper(Str::random(18)),
            'idempotency_key' => $key,
            'amount' => $assignment->amount_due,
            'currency' => strtoupper($assignment->currency),
            'status' => 'pending',
            'safe_metadata' => ['fee_assignment_id' => $assignment->id],
        ]);
        try {
            $session = $provider->initiate($transaction, (string) $user->email, (string) $user->name);
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
