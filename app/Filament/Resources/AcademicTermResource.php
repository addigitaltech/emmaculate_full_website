<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicTermResource\Pages;
use App\Filament\Resources\AcademicTermResource\RelationManagers;
use App\Models\AcademicTerm;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicTermResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?string $navigationGroup = 'School Management';
    protected static ?string $model = AcademicTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('academic_session_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('sequence')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_current')
                    ->required(),
                Forms\Components\DatePicker::make('starts_on'),
                Forms\Components\DatePicker::make('ends_on'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academic_session_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sequence')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_on')
                    ->date()
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
            'index' => Pages\ListAcademicTerms::route('/'),
            'create' => Pages\CreateAcademicTerm::route('/create'),
            'edit' => Pages\EditAcademicTerm::route('/{record}/edit'),
        ];
    }
}
