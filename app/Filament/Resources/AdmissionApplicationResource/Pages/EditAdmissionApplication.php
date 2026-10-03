<?php

namespace App\Filament\Resources\AdmissionApplicationResource\Pages;

use App\Filament\Resources\AdmissionApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionApplication extends EditRecord
{
    protected static string $resource = AdmissionApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
