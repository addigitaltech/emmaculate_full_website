<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\FlutterwaveGateway;
use App\Domain\Payments\Gateways\MoniepointPosGateway;
use App\Domain\Payments\Gateways\PaystackGateway;

final class PaymentGatewayManager
{
    public function resolve(string $driver): PaymentGateway
    {
        return match ($driver) {
            'paystack' => app(PaystackGateway::class),
            'flutterwave' => app(FlutterwaveGateway::class),
            'moniepoint' => app(MoniepointPosGateway::class),
            default => throw new PaymentGatewayException('Unknown payment gateway.'),
        };
    }

    /** @return list<PaymentGateway> */
    public function available(): array
    {
        return [
            app(PaystackGateway::class),
            app(FlutterwaveGateway::class),
            app(MoniepointPosGateway::class),
        ];
    }
}
