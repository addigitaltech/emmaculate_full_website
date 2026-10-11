<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryAlbumResource\Pages;
use App\Filament\Resources\GalleryAlbumResource\RelationManagers;
use App\Models\GalleryAlbum;
use Filament\Forms;
use App\Filament\Support\MediaField;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GalleryAlbumResource extends AuthorizedResource
{
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $navigationLabel = 'Gallery';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?int $navigationSort = 4;
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $model = GalleryAlbum::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Album')->schema([
                Forms\Components\TextInput::make('title')->label('Album name')->required()->maxLength(120)->helperText('Examples: Sports, Academics, School Life, Events.'),
                Forms\Components\Select::make('status')->label('Show on the website?')
                    ->options(['published' => 'Published (visible on the website)', 'draft' => 'Unpublished (hidden)'])->default('published')->required()->native(false),
                Forms\Components\Textarea::make('description')->label('About this album (optional)')->rows(2)->columnSpanFull(),
                MediaField::image('cover_image_id', 'Cover picture (optional)'),
            ])->columns(2),
            Forms\Components\Section::make('Photos')
                ->description('Add photos from the Media Library or upload new ones. Drag to change the order.')
                ->schema([
                    Forms\Components\Repeater::make('allImages')
                        ->relationship('allImages')
                        ->label('')
                        ->schema([
                            MediaField::image('media_id', 'Photo')->required(),
                            Forms\Components\TextInput::make('caption')->label('Caption (optional)')->maxLength(160),
                            Forms\Components\Toggle::make('is_visible')->label('Show this photo')->default(true),
                        ])
                        ->columns(3)
                        ->orderColumn('sort_order')
                        ->itemLabel(fn (array $state): ?string => $state['caption'] ?? null)
                        ->addActionLabel('Add a photo')
                        ->defaultItems(0)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
            Forms\Components\Hidden::make('sort_order')->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('coverImage.path')->label('Cover')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Album')->searchable(),
                Tables\Columns\TextColumn::make('all_images_count')->counts('allImages')->label('Photos'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'published' ? 'Published' : 'Unpublished')
                    ->color(fn (?string $state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleryAlbums::route('/'),
            'create' => Pages\CreateGalleryAlbum::route('/create'),
            'edit' => Pages\EditGalleryAlbum::route('/{record}/edit'),
        ];
    }
}
