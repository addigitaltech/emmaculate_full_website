<?php

namespace App\Filament\Resources\TermRemarkResource\Pages;

use App\Filament\Resources\TermRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTermRemarks extends ListRecords
{
    protected static string $resource = TermRemarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
