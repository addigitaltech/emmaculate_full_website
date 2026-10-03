<?php

namespace App\Filament\Resources\TermRemarkResource\Pages;

use App\Filament\Resources\TermRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTermRemark extends EditRecord
{
    protected static string $resource = TermRemarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
