<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherAssignmentResource\Pages;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherAssignmentResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage teachers';
    protected static ?string $navigationGroup = 'School Management';
    protected static ?string $model = TeacherAssignment::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('teacher_id')->label('Teacher')->options(fn () => Teacher::query()->where('status', 'active')->orderBy('last_name')->get()->mapWithKeys(fn (Teacher $teacher) => [$teacher->id => $teacher->fullName().($teacher->staff_number ? ' · '.$teacher->staff_number : '')]))->searchable()->required(),
            Forms\Components\Select::make('school_class_id')->label('Class')->options(fn () => SchoolClass::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->required()->live()->afterStateUpdated(function (Set $set): void { $set('arm_id', null); $set('subject_id', null); }),
            Forms\Components\Select::make('arm_id')->label('Arm (blank assigns all arms)')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->orderBy('name')->pluck('name', 'id'))->searchable()->placeholder('All class arms'),
            Forms\Components\Select::make('subject_id')->label('Subject')->options(fn (Get $get) => Subject::query()->where('status', 'active')->when($get('school_class_id'), fn ($query, $classId) => $query->where(fn ($q) => $q->whereNull('school_class_id')->orWhere('school_class_id', $classId)))->when($get('arm_id'), fn ($query, $armId) => $query->where(fn ($q) => $q->whereNull('arm_id')->orWhere('arm_id', $armId)))->orderBy('name')->pluck('name', 'id'))->searchable()->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['teacher', 'schoolClass', 'arm', 'subject']))->columns([
            Tables\Columns\TextColumn::make('teacher.last_name')->label('Teacher')->formatStateUsing(fn ($state, TeacherAssignment $record) => $record->teacher?->fullName())->searchable(),
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
            Tables\Columns\TextColumn::make('arm.name')->label('Arm')->placeholder('All arms'),
            Tables\Columns\TextColumn::make('subject.name')->label('Subject')->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListTeacherAssignments::route('/'), 'create' => Pages\CreateTeacherAssignment::route('/create'), 'edit' => Pages\EditTeacherAssignment::route('/{record}/edit')]; }
}
