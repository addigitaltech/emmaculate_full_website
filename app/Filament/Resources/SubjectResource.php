<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use App\Models\SubjectCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Subjects';
    protected static ?string $navigationGroup = 'Classes & subjects';
    protected static ?string $model = Subject::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->maxLength(40)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->required()->maxLength(140),
            Forms\Components\Select::make('subject_category_id')->label('Category')->options(fn () => SubjectCategory::query()->orderBy('name')->pluck('name', 'id'))->searchable()->preload(),
            Forms\Components\Select::make('schoolClasses')->label('Classes (select any number)')->relationship('schoolClasses', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->multiple()->searchable()->preload()->helperText('Select one, two, three, four or as many classes as this subject is taught in. Leave empty for a school-wide shared subject.'),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['schoolClasses', 'schoolClass', 'arm', 'category']))->columns([
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('category.name')->label('Category')->toggleable(),
            Tables\Columns\TextColumn::make('schoolClasses.name')->label('Classes')->listWithLineBreaks()->limitList(5)->expandableLimitedList(),
            Tables\Columns\TextColumn::make('arm.name')->label('Arm'),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListSubjects::route('/'), 'create' => Pages\CreateSubject::route('/create'), 'edit' => Pages\EditSubject::route('/{record}/edit')]; }
}
