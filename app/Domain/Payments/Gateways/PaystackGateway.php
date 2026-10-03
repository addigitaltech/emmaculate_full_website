<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\PaymentGatewayException;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use JsonException;

class PaystackGateway implements PaymentGateway
{
    public function driver(): string { return 'paystack'; }
    public function displayName(): string { return 'Paystack'; }
    public function supportsOnlineCheckout(): bool { return true; }
    public function supportsRefunds(): bool { return true; }

    public function initiate(PaymentTransaction $transaction, string $customerEmail, string $customerName): array
    {
        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            throw new PaymentGatewayException('Paystack has not been configured by the school.');
        }
        $response = Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
            ->withToken($secret)->acceptJson()->timeout(20)
            ->post('/transaction/initialize', [
                'email' => $customerEmail,
                'amount' => (int) round((float) $transaction->amount * 100),
                'currency' => $transaction->currency,
                'reference' => $transaction->reference,
                'callback_url' => route('payments.return'),
                'metadata' => ['student_id' => $transaction->student_id, 'transaction_id' => $transaction->id],
            ]);
        if (! $response->successful() || ! $response->json('status') || ! $response->json('data.authorization_url')) {
            throw new PaymentGatewayException('Paystack did not return a valid checkout session.');
        }
        return [
            'authorization_url' => (string) $response->json('data.authorization_url'),
            'provider_reference' => (string) $response->json('data.reference', $transaction->reference),
            'provider_id' => $response->json('data.id'),
        ];
    }

    public function verify(PaymentTransaction $transaction, ?string $providerReference = null): array
    {
        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            throw new PaymentGatewayException('Paystack has not been configured by the school.');
        }
        $response = Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
            ->withToken($secret)->acceptJson()->timeout(20)
            ->get('/transaction/verify/'.rawurlencode($transaction->reference));
        $data = $response->json('data', []);
        $amountMinor = (int) round((float) $transaction->amount * 100);
        $verified = $response->successful()
            && $response->json('status') === true
            && ($data['status'] ?? null) === 'success'
            && hash_equals($transaction->reference, (string) ($data['reference'] ?? ''))
            && (int) ($data['amount'] ?? -1) === $amountMinor
            && strtoupper((string) ($data['currency'] ?? '')) === strtoupper($transaction->currency);
        return ['verified' => $verified, 'provider_reference' => (string) ($data['reference'] ?? ''), 'status' => (string) ($data['status'] ?? 'unknown')];
    }

    public function authenticateWebhook(string $rawBody, array $headers): bool
    {
        $secret = (string) config('services.paystack.secret_key');
        $signature = $this->header($headers, 'x-paystack-signature');
        return $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha512', $rawBody, $secret), $signature);
    }

    public function parseWebhook(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new PaymentGatewayException('Invalid Paystack webhook JSON.');
        }
        return is_array($payload) ? $payload : [];
    }

    public function refund(PaymentTransaction $transaction): array
    {
        if (! $transaction->provider_reference) {
            throw new PaymentGatewayException('The Paystack transaction reference is unavailable for refund.');
        }
        $secret = (string) config('services.paystack.secret_key');
        $response = Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
            ->withToken($secret)->acceptJson()->timeout(20)
            ->post('/refund', ['transaction' => $transaction->provider_reference ?: $transaction->reference]);
        $data = $response->json('data', []);
        $amountMinor = (int) round((float) $transaction->amount * 100);
        if (! $response->successful() || $response->json('status') !== true
            || ! is_array($data) || (int) ($data['amount'] ?? -1) !== $amountMinor
            || strtoupper((string) ($data['currency'] ?? '')) !== strtoupper($transaction->currency)
            || ! hash_equals($transaction->reference, (string) ($data['transaction']['reference'] ?? ''))) {
            throw new PaymentGatewayException('Paystack did not accept the refund request.');
        }
        return ['accepted' => true, 'reference' => isset($data['id']) ? (string) $data['id'] : null, 'status' => (string) ($data['status'] ?? 'pending')];
    }

    private function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $name) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }
        return '';
    }
}
