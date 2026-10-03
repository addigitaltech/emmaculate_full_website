<?php

namespace App\Filament\Resources\GradeBandResource\Pages;

use App\Filament\Resources\GradeBandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGradeBand extends EditRecord
{
    protected static string $resource = GradeBandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
