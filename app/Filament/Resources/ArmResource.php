<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArmResource\Pages;
use App\Filament\Resources\ArmResource\RelationManagers;
use App\Models\Arm;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArmResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Arms';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $model = Arm::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
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
            'index' => Pages\ListArms::route('/'),
            'create' => Pages\CreateArm::route('/create'),
            'edit' => Pages\EditArm::route('/{record}/edit'),
        ];
    }
}
