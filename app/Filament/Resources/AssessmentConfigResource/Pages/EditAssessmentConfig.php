<?php

namespace App\Filament\Resources\AssessmentConfigResource\Pages;

use App\Filament\Resources\AssessmentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentConfig extends EditRecord
{
    protected static string $resource = AssessmentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
