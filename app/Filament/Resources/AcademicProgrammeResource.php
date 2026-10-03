<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicProgrammeResource\Pages;
use App\Filament\Resources\AcademicProgrammeResource\RelationManagers;
use App\Models\AcademicProgramme;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicProgrammeResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = AcademicProgramme::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('level')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\TextInput::make('slug')
                    ->required(),
                Forms\Components\Textarea::make('intro')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('approach')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('placeholder_note')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('curriculum')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_id')
                    ->image(),
                Forms\Components\FileUpload::make('image_path')
                    ->image(),
                Forms\Components\Toggle::make('is_published')
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('seo_title'),
                Forms\Components\Textarea::make('seo_description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('level')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image_path'),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seo_title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListAcademicProgrammes::route('/'),
            'create' => Pages\CreateAcademicProgramme::route('/create'),
            'edit' => Pages\EditAcademicProgramme::route('/{record}/edit'),
        ];
    }
}
