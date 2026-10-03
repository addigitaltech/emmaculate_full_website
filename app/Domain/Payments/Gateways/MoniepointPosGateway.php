<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\PaymentGatewayException;
use App\Models\PaymentTransaction;

final class MoniepointPosGateway implements PaymentGateway
{
    public function driver(): string { return 'moniepoint'; }
    public function displayName(): string { return 'Moniepoint POS'; }
    public function supportsOnlineCheckout(): bool { return false; }
    public function supportsRefunds(): bool { return false; }

    public function initiate(PaymentTransaction $transaction, string $customerEmail, string $customerName): array
    {
        throw new PaymentGatewayException('Moniepoint POS is not an online checkout gateway. Confirm the school\'s merchant product and authorized API contract before enabling payments.');
    }

    public function verify(PaymentTransaction $transaction, ?string $providerReference = null): array
    {
        throw new PaymentGatewayException('Moniepoint POS transaction lookup requires the school\'s authorized merchant configuration.');
    }

    public function authenticateWebhook(string $rawBody, array $headers): bool
    {
        // The inspected POS webhook contract does not document the same signature as Paystack/Flutterwave.
        // Fail closed until the exact account's documented authentication setup is configured.
        return false;
    }

    public function parseWebhook(string $rawBody): array
    {
        throw new PaymentGatewayException('Moniepoint webhook processing is disabled until the exact merchant product and authentication contract are confirmed.');
    }

    public function refund(PaymentTransaction $transaction): array
    {
        throw new PaymentGatewayException('Refunds are not enabled for this unconfirmed Moniepoint integration.');
    }
}
