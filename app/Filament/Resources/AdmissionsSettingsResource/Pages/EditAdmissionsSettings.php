<?php

namespace App\Filament\Resources\AdmissionsSettingsResource\Pages;

use App\Filament\Resources\AdmissionsSettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionsSettings extends EditRecord
{
    protected static string $resource = AdmissionsSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
