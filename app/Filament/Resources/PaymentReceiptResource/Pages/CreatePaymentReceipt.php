<?php

namespace App\Filament\Resources\PaymentReceiptResource\Pages;

use App\Filament\Resources\PaymentReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentReceipt extends CreateRecord
{
    protected static string $resource = PaymentReceiptResource::class;
}
