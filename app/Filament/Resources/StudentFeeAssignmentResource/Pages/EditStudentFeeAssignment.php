<?php

namespace App\Filament\Resources\StudentFeeAssignmentResource\Pages;

use App\Filament\Resources\StudentFeeAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentFeeAssignment extends EditRecord
{
    protected static string $resource = StudentFeeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
