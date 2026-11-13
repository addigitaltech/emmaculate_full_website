<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaecSubjectCodeResource\Pages;
use App\Models\WaecSubjectCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class WaecSubjectCodeResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'WAEC subject codes';
    protected static ?string $navigationGroup = 'Classes & subjects';
    protected static ?string $model = WaecSubjectCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('subject_name')->label('Subject')->required()->maxLength(140),
            Forms\Components\TextInput::make('code')->label('WAEC code')->required()->maxLength(10)->unique(ignoreRecord: true),
            Forms\Components\Toggle::make('is_active')->label('Active')->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('subject_name')->label('Subject')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code')->label('WAEC code')->searchable()->sortable(),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWaecSubjectCodes::route('/'), 'create' => Pages\CreateWaecSubjectCode::route('/create'), 'edit' => Pages\EditWaecSubjectCode::route('/{record}/edit')];
    }
}
