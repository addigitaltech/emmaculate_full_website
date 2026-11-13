<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectCategoryResource\Pages;
use App\Models\SubjectCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectCategoryResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Subject categories';
    protected static ?string $navigationGroup = 'Classes & subjects';
    protected static ?string $model = SubjectCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(100),
            Forms\Components\TextInput::make('code')->required()->maxLength(40)->unique(ignoreRecord: true),
            Forms\Components\Toggle::make('is_active')->label('Active')->default(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubjectCategories::route('/'), 'create' => Pages\CreateSubjectCategory::route('/create'), 'edit' => Pages\EditSubjectCategory::route('/{record}/edit')];
    }
}
