<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages;
use App\Models\SchoolClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolClassResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Classes';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $model = SchoolClass::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(100)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('level')->maxLength(100),
            Forms\Components\Toggle::make('is_active')->default(true)->required(),
            Forms\Components\Select::make('arms')->relationship('arms', 'name')->multiple()->searchable()->preload()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('arms'))->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('level')->searchable(),
            Tables\Columns\TextColumn::make('arms.name')->label('Arms')->badge()->separator(', '),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListSchoolClasses::route('/'), 'create' => Pages\CreateSchoolClass::route('/create'), 'edit' => Pages\EditSchoolClass::route('/{record}/edit')]; }
}
