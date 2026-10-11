<?php

namespace App\Filament\Support;

use App\Models\MediaAsset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

/**
 * One friendly picture field for the whole admin: pick a picture already in the Media Library
 * or press "+" to upload a new one from the phone or computer (it is saved to the Media Library).
 */
final class MediaField
{
    /** Stores the media library ID (for columns such as image_id, cover_image_id, photo_id). */
    public static function image(string $name = 'image_id', string $label = 'Picture'): Select
    {
        return self::build($name, $label, 'id');
    }

    /** Stores the file path (for columns such as logo_path). */
    public static function path(string $name, string $label): Select
    {
        return self::build($name, $label, 'path');
    }

    private static function build(string $name, string $label, string $key): Select
    {
        $images = fn (): Builder => MediaAsset::query()->where('mime_type', 'like', 'image/%');

        return Select::make($name)
            ->label($label)
            ->options(fn () => $images()->orderByDesc('id')->limit(200)->pluck('original_name', $key))
            ->getSearchResultsUsing(fn (string $search) => $images()->where('original_name', 'like', '%'.$search.'%')->orderByDesc('id')->limit(50)->pluck('original_name', $key))
            ->getOptionLabelUsing(fn ($value) => $images()->where($key, $value)->value('original_name') ?? (string) $value)
            ->searchable()
            ->placeholder('Choose a picture, or press + to upload a new one')
            ->createOptionForm([
                FileUpload::make('file')
                    ->label('Upload from your phone or computer')
                    ->image()
                    ->imageResizeMode('contain')
                    ->imageResizeTargetWidth('2000')
                    ->imageResizeTargetHeight('2000')
                    ->imageResizeUpscale(false)
                    ->maxSize(5120)
                    ->disk('public')
                    ->directory('media-library')
                    ->visibility('public')
                    ->storeFileNamesIn('original_name')
                    ->required(),
                TextInput::make('alt_text')->label('Short description of the picture (optional)')->maxLength(180),
            ])
            ->createOptionUsing(function (array $data) use ($key) {
                $asset = MediaUploader::fromStoredPath((string) ($data['file'] ?? ''), $data['original_name'] ?? null, $data['alt_text'] ?? null);

                return $key === 'path' ? $asset->path : $asset->id;
            })
            ->createOptionModalHeading('Upload a new picture')
            ->helperText('Pictures you upload are kept in the Media Library so you can use them again anywhere.');
    }
}
