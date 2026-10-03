<?php

namespace App\Filament\Resources\StudentFeeAssignmentResource\Pages;

use App\Filament\Resources\StudentFeeAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentFeeAssignments extends ListRecords
{
    protected static string $resource = StudentFeeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
