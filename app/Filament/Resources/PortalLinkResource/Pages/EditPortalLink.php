<?php

namespace App\Filament\Resources\PortalLinkResource\Pages;

use App\Filament\Resources\PortalLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPortalLink extends EditRecord
{
    protected static string $resource = PortalLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
