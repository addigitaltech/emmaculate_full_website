<?php

namespace App\Filament\Resources\AffectiveRatingResource\Pages;

use App\Filament\Resources\AffectiveRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAffectiveRating extends EditRecord
{
    protected static string $resource = AffectiveRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
