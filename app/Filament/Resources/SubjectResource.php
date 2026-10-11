<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Subjects';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $model = Subject::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->maxLength(40)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->required()->maxLength(140),
            Forms\Components\Select::make('school_class_id')->label('Class (leave blank for shared subject)')->relationship('schoolClass', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->live()->afterStateUpdated(fn ($set) => $set('arm_id', null)),
            Forms\Components\Select::make('arm_id')->label('Arm (optional)')->options(fn (Get $get) => \App\Models\Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->orderBy('name')->pluck('name', 'id'))->searchable()->placeholder('All arms'),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['schoolClass', 'arm']))->columns([
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
            Tables\Columns\TextColumn::make('arm.name')->label('Arm'),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListSubjects::route('/'), 'create' => Pages\CreateSubject::route('/create'), 'edit' => Pages\EditSubject::route('/{record}/edit')]; }
}
