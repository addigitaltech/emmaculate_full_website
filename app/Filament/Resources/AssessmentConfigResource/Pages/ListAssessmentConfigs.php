<?php

namespace App\Filament\Resources\AssessmentConfigResource\Pages;

use App\Filament\Resources\AssessmentConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentConfigs extends ListRecords
{
    protected static string $resource = AssessmentConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
