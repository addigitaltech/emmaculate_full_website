<?php

namespace App\Filament\Resources\PaymentEventResource\Pages;

use App\Filament\Resources\PaymentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentEvent extends CreateRecord
{
    protected static string $resource = PaymentEventResource::class;
}
