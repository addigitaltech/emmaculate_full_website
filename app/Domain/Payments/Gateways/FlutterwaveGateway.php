<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\PaymentGatewayException;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use JsonException;

class FlutterwaveGateway implements PaymentGateway
{
    public function driver(): string { return 'flutterwave'; }
    public function displayName(): string { return 'Flutterwave'; }
    public function supportsOnlineCheckout(): bool { return true; }
    public function supportsRefunds(): bool { return true; }

    public function initiate(PaymentTransaction $transaction, string $customerEmail, string $customerName): array
    {
        $secret = (string) config('services.flutterwave.secret_key');
        if ($secret === '') {
            throw new PaymentGatewayException('Flutterwave has not been configured by the school.');
        }
        $response = Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken($secret)->acceptJson()->timeout(20)
            ->post('/payments', [
                'tx_ref' => $transaction->reference,
                'amount' => number_format((float) $transaction->amount, 2, '.', ''),
                'currency' => $transaction->currency,
                'redirect_url' => route('payments.return'),
                'customer' => ['email' => $customerEmail, 'name' => $customerName],
                'meta' => ['student_id' => $transaction->student_id, 'transaction_id' => $transaction->id],
                'customizations' => ['title' => (string) config('app.name'), 'description' => 'School fee payment'],
            ]);
        if (! $response->successful() || $response->json('status') !== 'success' || ! $response->json('data.link')) {
            throw new PaymentGatewayException('Flutterwave did not return a valid checkout session.');
        }
        return ['authorization_url' => (string) $response->json('data.link'), 'provider_reference' => $transaction->reference, 'provider_id' => null];
    }

    public function verify(PaymentTransaction $transaction, ?string $providerReference = null): array
    {
        $secret = (string) config('services.flutterwave.secret_key');
        if ($secret === '' || ! $providerReference || ! ctype_digit($providerReference)) {
            throw new PaymentGatewayException('A configured Flutterwave key and provider transaction ID are required for verification.');
        }
        $response = Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken($secret)->acceptJson()->timeout(20)
            ->get('/transactions/'.rawurlencode($providerReference).'/verify');
        $data = $response->json('data', []);
        $verified = $response->successful()
            && $response->json('status') === 'success'
            && ($data['status'] ?? null) === 'successful'
            && hash_equals($transaction->reference, (string) ($data['tx_ref'] ?? ''))
            && abs((float) ($data['amount'] ?? -1) - (float) $transaction->amount) < 0.01
            && strtoupper((string) ($data['currency'] ?? '')) === strtoupper($transaction->currency);
        return ['verified' => $verified, 'provider_reference' => (string) ($data['id'] ?? $providerReference), 'status' => (string) ($data['status'] ?? 'unknown')];
    }

    public function authenticateWebhook(string $rawBody, array $headers): bool
    {
        $expected = (string) config('services.flutterwave.secret_hash');
        $received = $this->header($headers, 'verif-hash');
        return $expected !== '' && $received !== '' && hash_equals($expected, $received);
    }

    public function parseWebhook(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new PaymentGatewayException('Invalid Flutterwave webhook JSON.');
        }
        return is_array($payload) ? $payload : [];
    }

    public function refund(PaymentTransaction $transaction): array
    {
        if (! $transaction->provider_reference || ! ctype_digit($transaction->provider_reference)) {
            throw new PaymentGatewayException('A verified Flutterwave transaction ID is required for refund.');
        }
        $secret = (string) config('services.flutterwave.secret_key');
        $response = Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken($secret)->acceptJson()->timeout(20)
            ->post('/transactions/'.rawurlencode($transaction->provider_reference).'/refund');
        $data = $response->json('data', []);
        if (! $response->successful() || $response->json('status') !== 'success' || ! is_array($data)
            || abs((float) ($data['amount_refunded'] ?? -1) - (float) $transaction->amount) >= 0.01) {
            throw new PaymentGatewayException('Flutterwave did not accept the refund request.');
        }
        return ['accepted' => true, 'reference' => (string) ($data['flw_ref'] ?? $data['id'] ?? ''), 'status' => (string) ($data['status'] ?? 'pending')];
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
