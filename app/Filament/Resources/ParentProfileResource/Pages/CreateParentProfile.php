<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use App\Filament\Concerns\HandlesAccountPassword;
use App\Filament\Resources\ParentProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateParentProfile extends CreateRecord
{
    use HandlesAccountPassword;

    protected static string $resource = ParentProfileResource::class;

    protected function accountRole(): string
    {
        return 'Parent';
    }
}
