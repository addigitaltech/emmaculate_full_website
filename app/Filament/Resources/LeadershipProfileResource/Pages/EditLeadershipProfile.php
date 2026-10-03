<?php

namespace App\Filament\Resources\LeadershipProfileResource\Pages;

use App\Filament\Resources\LeadershipProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeadershipProfile extends EditRecord
{
    protected static string $resource = LeadershipProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
