<?php

namespace App\Filament\Resources\PortalLinkResource\Pages;

use App\Filament\Resources\PortalLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPortalLinks extends ListRecords
{
    protected static string $resource = PortalLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
