<?php

namespace App\Filament\Resources\ArmResource\Pages;

use App\Filament\Resources\ArmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArm extends EditRecord
{
    protected static string $resource = ArmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
