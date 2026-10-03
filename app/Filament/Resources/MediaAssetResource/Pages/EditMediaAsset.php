<?php

namespace App\Filament\Resources\MediaAssetResource\Pages;

use App\Filament\Resources\MediaAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditMediaAsset extends EditRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $path = (string) ($data['path'] ?? '');
        if ($path !== (string) $this->record->path) {
            if (! str_starts_with($path, 'media-library/') || str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
                throw ValidationException::withMessages(['path' => 'Choose a valid uploaded file from the media library.']);
            }
            $mime = Storage::disk('public')->mimeType($path) ?: '';
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'], true)) {
                throw ValidationException::withMessages(['path' => 'Only JPEG, PNG, WebP, GIF and PDF files are accepted.']);
            }
            $size = Storage::disk('public')->size($path);
            if ($size > 10 * 1024 * 1024) {
                throw ValidationException::withMessages(['path' => 'Files must be 10 MB or smaller.']);
            }
            $data['mime_type'] = $mime;
            $data['size_bytes'] = $size;
            $data['original_name'] = $data['original_name'] ?: basename($path);
        }
        $data['disk'] = 'public';
        $data['uploaded_by'] = $this->record->uploaded_by;
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
