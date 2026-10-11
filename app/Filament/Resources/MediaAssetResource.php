<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class MediaAssetResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'upload media';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Media library';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = MediaAsset::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')->label('Upload image or PDF')->disk('public')->directory('media-library')->visibility('public')->storeFileNamesIn('original_name')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'])->maxSize(10240)->required()->columnSpanFull(),
            Forms\Components\TextInput::make('alt_text')->maxLength(250)->helperText('Required for meaningful public images.'),
            Forms\Components\TextInput::make('caption')->maxLength(500),
            Forms\Components\KeyValue::make('metadata')->keyLabel('Key')->valueLabel('Value')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('path')->disk('public')->square(),
            Tables\Columns\TextColumn::make('original_name')->searchable()->limit(36),
            Tables\Columns\TextColumn::make('mime_type')->badge(),
            Tables\Columns\TextColumn::make('size_bytes')->numeric()->sortable()->formatStateUsing(fn ($state) => number_format(((int) $state) / 1024).' KB'),
            Tables\Columns\TextColumn::make('alt_text')->limit(40)->toggleable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMediaAssets::route('/'), 'create' => Pages\CreateMediaAsset::route('/create'), 'edit' => Pages\EditMediaAsset::route('/{record}/edit')];
    }
}
