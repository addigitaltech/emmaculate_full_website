<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadershipProfileResource\Pages;
use App\Filament\Resources\LeadershipProfileResource\RelationManagers;
use App\Models\LeadershipProfile;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadershipProfileResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = LeadershipProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\TextInput::make('photo_id')
                    ->numeric(),
                Forms\Components\TextInput::make('photo_path'),
                Forms\Components\Textarea::make('biography')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('qualifications'),
                Forms\Components\Toggle::make('is_visible')
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('photo_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('photo_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qualifications')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
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
            'index' => Pages\ListLeadershipProfiles::route('/'),
            'create' => Pages\CreateLeadershipProfile::route('/create'),
            'edit' => Pages\EditLeadershipProfile::route('/{record}/edit'),
        ];
    }
}
