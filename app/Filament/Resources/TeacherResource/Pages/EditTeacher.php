<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Concerns\HandlesAccountPassword;
use App\Filament\Resources\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacher extends EditRecord
{
    use HandlesAccountPassword;

    protected static string $resource = TeacherResource::class;

    protected function accountRole(): string
    {
        return 'Teacher';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
