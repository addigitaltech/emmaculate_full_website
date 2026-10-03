<?php

namespace App\Filament\Resources\StudentFeeAssignmentResource\Pages;

use App\Filament\Resources\StudentFeeAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentFeeAssignment extends CreateRecord
{
    protected static string $resource = StudentFeeAssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'unpaid';
        $data['assigned_by'] = auth()->id();
        return $data;
    }
}
