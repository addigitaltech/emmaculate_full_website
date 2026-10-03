<?php

namespace App\Filament\Resources\GalleryAlbumResource\Pages;

use App\Filament\Resources\GalleryAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGalleryAlbums extends ListRecords
{
    protected static string $resource = GalleryAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
