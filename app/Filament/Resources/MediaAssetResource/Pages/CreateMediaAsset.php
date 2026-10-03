<?php

namespace App\Filament\Resources\MediaAssetResource\Pages;

use App\Filament\Resources\MediaAssetResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateMediaAsset extends CreateRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $path = (string) ($data['path'] ?? '');
        if (! str_starts_with($path, 'media-library/') || str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
            throw ValidationException::withMessages(['path' => 'Choose a valid uploaded file from the media library.']);
        }
        $mime = Storage::disk('public')->mimeType($path) ?: '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (! in_array($mime, $allowed, true)) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages(['path' => 'Only JPEG, PNG, WebP, GIF and PDF files are accepted.']);
        }
        $size = Storage::disk('public')->size($path);
        if ($size > 10 * 1024 * 1024) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages(['path' => 'Files must be 10 MB or smaller.']);
        }
        $data['disk'] = 'public';
        $data['uploaded_by'] = auth()->id();
        $data['mime_type'] = $mime;
        $data['size_bytes'] = $size;
        $data['original_name'] = $data['original_name'] ?: basename($path);
        return $data;
    }
}
