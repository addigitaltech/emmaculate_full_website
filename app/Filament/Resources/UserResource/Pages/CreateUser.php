<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // A user given the Teacher, Parent or Student role automatically gets the matching school record.
        \App\Domain\Identity\IdentitySync::forUser($this->record->fresh());
    }
}
