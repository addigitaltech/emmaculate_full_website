<?php

namespace App\Filament\Resources\PaymentEventResource\Pages;

use App\Filament\Resources\PaymentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentEvent extends EditRecord
{
    protected static string $resource = PaymentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
