<?php

namespace App\Filament\Resources\CmsSectionResource\Pages;

use App\Filament\Resources\CmsSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsSections extends ListRecords
{
    protected static string $resource = CmsSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
