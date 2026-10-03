<?php

namespace App\Filament\Resources\AcademicProgrammeResource\Pages;

use App\Filament\Resources\AcademicProgrammeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicProgramme extends EditRecord
{
    protected static string $resource = AcademicProgrammeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
