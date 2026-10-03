<?php

namespace App\Domain\Payments;

use App\Models\PaymentTransaction;
use InvalidArgumentException;

final class PaymentStatusTransition
{
    private const ALLOWED = [
        'pending' => ['processing', 'successful', 'failed', 'cancelled'],
        'processing' => ['successful', 'failed', 'cancelled'],
        'successful' => ['refunded'],
        'failed' => [],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function transition(PaymentTransaction $transaction, string $next, bool $providerVerified = false): void
    {
        $current = $transaction->status;
        if (! in_array($next, self::ALLOWED[$current] ?? [], true)) {
            throw new InvalidArgumentException("Payment cannot transition from {$current} to {$next}.");
        }
        if ($next === 'successful' && ! $providerVerified) {
            throw new InvalidArgumentException('Only a verified provider result can mark a payment successful.');
        }
        if ($next === 'refunded' && ! $providerVerified) {
            throw new InvalidArgumentException('Only a confirmed provider refund can mark a payment refunded.');
        }
        $transaction->status = $next;
        if ($next === 'successful') {
            $transaction->verified_at = now();
        }
        if ($next === 'refunded') {
            $transaction->refunded_at = now();
        }
        $transaction->save();
    }
}
