<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermRemarkResource\Pages;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\TermRemark;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;

class TermRemarkResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage results';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 92;
    protected static ?string $navigationLabel = 'Remarks';
    protected static ?string $navigationGroup = 'Results';
    protected static ?string $model = TermRemark::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->relationship('student', 'student_number', modifyQueryUsing: fn ($query) => $query->where('status', 'active'))->searchable()->preload()->getOptionLabelFromRecordUsing(fn (Student $student) => $student->fullName().' · '.$student->student_number)->required(),
            Forms\Components\Select::make('academic_session_id')->relationship('session', 'name')->searchable()->preload()->live()->required()->afterStateUpdated(fn (Set $set) => $set('academic_term_id', null)),
            Forms\Components\Select::make('academic_term_id')->label('Term')->options(fn (Get $get) => AcademicTerm::query()->when($get('academic_session_id'), fn ($query, $id) => $query->where('academic_session_id', $id))->orderBy('sequence')->pluck('name', 'id'))->searchable()->required(),
            Forms\Components\Textarea::make('teacher_remark')->maxLength(2000)->columnSpanFull(),
            Forms\Components\Textarea::make('principal_remark')->maxLength(2000)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['student', 'session', 'term']))->columns([
            Tables\Columns\TextColumn::make('student.student_number')->label('Student')->searchable(),
            Tables\Columns\TextColumn::make('session.name')->label('Session'),
            Tables\Columns\TextColumn::make('term.name')->label('Term'),
            Tables\Columns\TextColumn::make('editor.name')->label('Updated by'),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListTermRemarks::route('/'), 'create' => Pages\CreateTermRemark::route('/create'), 'edit' => Pages\EditTermRemark::route('/{record}/edit')]; }
}
