<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffectiveTraitResource\Pages;
use App\Filament\Resources\AffectiveTraitResource\RelationManagers;
use App\Models\AffectiveTrait;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AffectiveTraitResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage results';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'Behaviour & skills list';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $model = AffectiveTrait::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('category')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
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
            'index' => Pages\ListAffectiveTraits::route('/'),
            'create' => Pages\CreateAffectiveTrait::route('/create'),
            'edit' => Pages\EditAffectiveTrait::route('/{record}/edit'),
        ];
    }
}
