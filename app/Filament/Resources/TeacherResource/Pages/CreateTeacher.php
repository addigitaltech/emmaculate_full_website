<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Concerns\HandlesAccountPassword;
use App\Filament\Resources\TeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    use HandlesAccountPassword;

    protected static string $resource = TeacherResource::class;

    protected function accountRole(): string
    {
        return 'Teacher';
    }
}
