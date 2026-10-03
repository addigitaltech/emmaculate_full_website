<?php

namespace App\Filament\Resources\LeadershipProfileResource\Pages;

use App\Filament\Resources\LeadershipProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeadershipProfiles extends ListRecords
{
    protected static string $resource = LeadershipProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
