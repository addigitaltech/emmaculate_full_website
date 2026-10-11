<?php

namespace App\Filament\Support;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Turns a file uploaded in an admin form into an entry in the Media Library. */
final class MediaUploader
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public static function fromStoredPath(string $path, ?string $originalName = null, ?string $altText = null): MediaAsset
    {
        $disk = Storage::disk('public');
        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, 'media-library/') || ! $disk->exists($path)) {
            throw ValidationException::withMessages(['file' => 'The upload could not be found. Please try again.']);
        }

        $mime = (string) $disk->mimeType($path);
        $size = (int) $disk->size($path);
        $isRealImage = @getimagesize($disk->path($path)) !== false;
        if (! $isRealImage || ! in_array($mime, self::ALLOWED, true) || $size > 5 * 1024 * 1024) {
            $disk->delete($path);
            throw ValidationException::withMessages(['file' => 'Please upload a real JPEG, PNG, WebP or GIF picture of at most 5 MB.']);
        }

        $name = trim((string) $originalName) !== '' ? basename(str_replace('\\', '/', (string) $originalName)) : basename($path);

        return MediaAsset::query()->create([
            'uploaded_by' => auth()->id(),
            'original_name' => Str::limit($name, 150, ''),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $mime,
            'size_bytes' => $size,
            'alt_text' => filled($altText) ? Str::limit(trim((string) $altText), 180, '') : null,
        ]);
    }
}
