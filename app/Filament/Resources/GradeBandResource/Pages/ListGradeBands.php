<?php

namespace App\Filament\Resources\GradeBandResource\Pages;

use App\Filament\Resources\GradeBandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGradeBands extends ListRecords
{
    protected static string $resource = GradeBandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
