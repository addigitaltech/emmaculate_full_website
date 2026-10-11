<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Arm;
use App\Models\MediaAsset;
use App\Models\SchoolClass;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage students';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'People';
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('student_number')->required()->maxLength(80)->unique(ignoreRecord: true),
            Forms\Components\Select::make('user_id')->relationship('user', 'email', modifyQueryUsing: fn ($query) => $query->role('Student'))->searchable()->preload()->placeholder('No portal account linked'),
            Forms\Components\TextInput::make('first_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('last_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('other_names')->maxLength(150),
            Forms\Components\Select::make('gender')->options(['Female' => 'Female', 'Male' => 'Male', 'Not recorded' => 'Not recorded'])->placeholder('Not recorded'),
            Forms\Components\DatePicker::make('date_of_birth')->maxDate(now()),
            Forms\Components\Select::make('school_class_id')->label('Class')->relationship('schoolClass', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->live()->afterStateUpdated(fn (Set $set) => $set('arm_id', null)),
            Forms\Components\Select::make('arm_id')->label('Arm')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->orderBy('name')->pluck('name', 'id'))->searchable()->placeholder('No arm'),
            Forms\Components\Select::make('photo_id')->label('Photo')->relationship('photo', 'original_name', modifyQueryUsing: fn ($query) => $query->where('mime_type', 'like', 'image/%'))->searchable()->preload()->placeholder('No photo'),
            Forms\Components\DatePicker::make('admission_date'),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated', 'transferred' => 'Transferred'])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['schoolClass', 'arm', 'user']))->columns([
            Tables\Columns\TextColumn::make('student_number')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('first_name')->searchable(),
            Tables\Columns\TextColumn::make('last_name')->searchable(),
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
            Tables\Columns\TextColumn::make('arm.name')->label('Arm'),
            Tables\Columns\TextColumn::make('user.email')->label('Portal account')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('admission_date')->date()->sortable()->toggleable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('school_class_id')->label('Class')->relationship('schoolClass', 'name'),
            Tables\Filters\SelectFilter::make('arm_id')->label('Arm')->relationship('arm', 'name'),
            Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated', 'transferred' => 'Transferred'])->default('active'),
        ])->actions([
            Tables\Actions\Action::make('enterResult')->label('Enter result')->icon('heroicon-o-pencil-square')->color('success')
                ->url(fn (Student $record) => route('staff.results.student', $record))
                ->visible(fn () => (bool) auth()->user()?->can('manage results')),
            Tables\Actions\Action::make('printResult')->label('Print result')->icon('heroicon-o-printer')->color('info')
                ->url(function (Student $record) {
                    $term = \App\Models\AcademicTerm::query()->where('is_current', true)->first();

                    return $term ? route('reports.show', ['student' => $record->id, 'term' => $term->id]) : null;
                }, shouldOpenInNewTab: true)
                ->visible(fn () => (bool) auth()->user()?->can('manage results')),
            Tables\Actions\EditAction::make(),
        ])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListStudents::route('/'), 'create' => Pages\CreateStudent::route('/create'), 'edit' => Pages\EditStudent::route('/{record}/edit')]; }
}
