<?php

namespace App\Filament\Resources\AcademicTermResource\Pages;

use App\Filament\Resources\AcademicTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicTerms extends ListRecords
{
    protected static string $resource = AcademicTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
