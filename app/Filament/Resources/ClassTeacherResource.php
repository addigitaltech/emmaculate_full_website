<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassTeacherResource\Pages;
use App\Models\Arm;
use App\Models\ClassTeacher;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;

class ClassTeacherResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage teachers';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Class teachers';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $model = ClassTeacher::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('teacher_id')->label('Class teacher')->options(fn () => Teacher::query()->where('status', 'active')->orderBy('last_name')->get()->mapWithKeys(fn (Teacher $teacher) => [$teacher->id => $teacher->fullName().($teacher->staff_number ? ' · '.$teacher->staff_number : '')]))->searchable()->required(),
            Forms\Components\Select::make('school_class_id')->label('Class')->options(fn () => SchoolClass::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->required()->live()->afterStateUpdated(fn (Set $set) => $set('arm_id', null)),
            Forms\Components\Select::make('arm_id')->label('Arm (leave blank only for a class without arms)')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->orderBy('name')->pluck('name', 'id'))->searchable(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['teacher', 'schoolClass', 'arm']))->columns([
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
            Tables\Columns\TextColumn::make('arm.name')->label('Arm')->placeholder('—'),
            Tables\Columns\TextColumn::make('teacher.last_name')->label('Class teacher')->formatStateUsing(fn ($state, ClassTeacher $record) => $record->teacher?->fullName())->searchable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListClassTeachers::route('/'), 'create' => Pages\CreateClassTeacher::route('/create'), 'edit' => Pages\EditClassTeacher::route('/{record}/edit')]; }
}
