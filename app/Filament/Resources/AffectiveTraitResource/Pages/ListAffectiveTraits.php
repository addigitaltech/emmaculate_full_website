<?php

namespace App\Filament\Resources\AffectiveTraitResource\Pages;

use App\Filament\Resources\AffectiveTraitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAffectiveTraits extends ListRecords
{
    protected static string $resource = AffectiveTraitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
