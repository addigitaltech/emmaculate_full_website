<?php

namespace App\Domain\Payments\Contracts;

use App\Models\PaymentTransaction;

interface PaymentGateway
{
    public function driver(): string;
    public function displayName(): string;
    public function supportsOnlineCheckout(): bool;
    public function supportsRefunds(): bool;
    public function initiate(PaymentTransaction $transaction, string $customerEmail, string $customerName): array;
    public function verify(PaymentTransaction $transaction, ?string $providerReference = null): array;
    public function authenticateWebhook(string $rawBody, array $headers): bool;
    public function parseWebhook(string $rawBody): array;
    public function refund(PaymentTransaction $transaction): array;
}
