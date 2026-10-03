<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsPostResource\Pages;
use App\Filament\Resources\NewsPostResource\RelationManagers;
use App\Models\NewsPost;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NewsPostResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = NewsPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('author_id')
                    ->numeric(),
                Forms\Components\FileUpload::make('cover_image_id')
                    ->image(),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\TextInput::make('slug')
                    ->required(),
                Forms\Components\TextInput::make('subtitle'),
                Forms\Components\Textarea::make('excerpt')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('content')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('category'),
                Forms\Components\Textarea::make('tags')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('is_featured')
                    ->required(),
                Forms\Components\DateTimePicker::make('published_at'),
                Forms\Components\DateTimePicker::make('expires_at'),
                Forms\Components\TextInput::make('seo_title'),
                Forms\Components\Textarea::make('seo_description')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('social_image_id')
                    ->image(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('cover_image_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seo_title')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('social_image_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListNewsPosts::route('/'),
            'create' => Pages\CreateNewsPost::route('/create'),
            'edit' => Pages\EditNewsPost::route('/{record}/edit'),
        ];
    }
}
