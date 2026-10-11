<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentConfigResource\Pages;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\AssessmentConfig;
use App\Models\SchoolClass;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;

class AssessmentConfigResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage academic structure';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 91;
    protected static ?string $navigationLabel = 'Assessment setup';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $model = AssessmentConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_session_id')->relationship('session', 'name')->required()->searchable()->preload()->live()->afterStateUpdated(fn (Set $set) => $set('academic_term_id', null)),
            Forms\Components\Select::make('academic_term_id')->label('Term')->options(fn (Get $get) => AcademicTerm::query()->when($get('academic_session_id'), fn ($query, $id) => $query->where('academic_session_id', $id))->orderBy('sequence')->get()->mapWithKeys(fn (AcademicTerm $term) => [$term->id => $term->name]))->required()->searchable(),
            Forms\Components\Select::make('school_class_id')->relationship('schoolClass', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->required()->searchable()->preload()->live()->afterStateUpdated(fn (Set $set) => $set('subject_id', null)),
            Forms\Components\Select::make('subject_id')->label('Subject')->options(fn (Get $get) => Subject::query()->where('status', 'active')->when($get('school_class_id'), fn ($query, $classId) => $query->where(fn ($q) => $q->whereNull('school_class_id')->orWhere('school_class_id', $classId)))->orderBy('name')->pluck('name', 'id'))->required()->searchable(),
            Forms\Components\TextInput::make('ca1_max')->label('CA 1 maximum')->numeric()->integer()->minValue(0)->maxValue(100)->default(40)->required(),
            Forms\Components\TextInput::make('ca2_max')->label('CA 2 maximum')->numeric()->integer()->minValue(0)->maxValue(100)->default(0)->required(),
            Forms\Components\TextInput::make('ca3_max')->label('CA 3 maximum')->numeric()->integer()->minValue(0)->maxValue(100)->default(0)->required(),
            Forms\Components\TextInput::make('exam_max')->label('Exam maximum')->numeric()->integer()->minValue(0)->maxValue(100)->default(60)->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['session', 'term', 'schoolClass', 'subject']))->columns([
            Tables\Columns\TextColumn::make('session.name')->label('Session')->sortable(),
            Tables\Columns\TextColumn::make('term.name')->label('Term'),
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Class'),
            Tables\Columns\TextColumn::make('subject.name')->label('Subject'),
            Tables\Columns\TextColumn::make('ca1_max')->label('CA1')->numeric(),
            Tables\Columns\TextColumn::make('ca2_max')->label('CA2')->numeric(),
            Tables\Columns\TextColumn::make('ca3_max')->label('CA3')->numeric(),
            Tables\Columns\TextColumn::make('exam_max')->label('Exam')->numeric(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListAssessmentConfigs::route('/'), 'create' => Pages\CreateAssessmentConfig::route('/create'), 'edit' => Pages\EditAssessmentConfig::route('/{record}/edit')]; }
}
