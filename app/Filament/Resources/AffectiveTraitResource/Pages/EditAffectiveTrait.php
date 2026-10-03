<?php

namespace App\Filament\Resources\AffectiveTraitResource\Pages;

use App\Filament\Resources\AffectiveTraitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAffectiveTrait extends EditRecord
{
    protected static string $resource = AffectiveTraitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
