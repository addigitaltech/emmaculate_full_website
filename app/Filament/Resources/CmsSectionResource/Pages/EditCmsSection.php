<?php

namespace App\Filament\Resources\CmsSectionResource\Pages;

use App\Filament\Resources\CmsSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsSection extends EditRecord
{
    protected static string $resource = CmsSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
