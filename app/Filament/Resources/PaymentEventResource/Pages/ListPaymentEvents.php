<?php

namespace App\Filament\Resources\PaymentEventResource\Pages;

use App\Filament\Resources\PaymentEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentEvents extends ListRecords
{
    protected static string $resource = PaymentEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
