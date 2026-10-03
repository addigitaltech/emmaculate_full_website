<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use App\Filament\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParentProfile extends EditRecord
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
