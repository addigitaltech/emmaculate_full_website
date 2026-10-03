<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffectiveRatingResource\Pages;
use App\Models\AcademicTerm;
use App\Models\AffectiveRating;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;

class AffectiveRatingResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage results';
    protected static ?string $navigationGroup = 'Results';
    protected static ?string $model = AffectiveRating::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->relationship('student', 'student_number', modifyQueryUsing: fn ($query) => $query->where('status', 'active'))->searchable()->preload()->getOptionLabelFromRecordUsing(fn (Student $student) => $student->fullName().' · '.$student->student_number)->required(),
            Forms\Components\Select::make('trait_id')->relationship('trait', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->required(),
            Forms\Components\Select::make('academic_session_id')->relationship('session', 'name')->searchable()->preload()->live()->required()->afterStateUpdated(fn (Set $set) => $set('academic_term_id', null)),
            Forms\Components\Select::make('academic_term_id')->label('Term')->options(fn (Get $get) => AcademicTerm::query()->when($get('academic_session_id'), fn ($query, $id) => $query->where('academic_session_id', $id))->orderBy('sequence')->pluck('name', 'id'))->searchable()->required(),
            Forms\Components\Select::make('rating')->options([1 => '1 — Needs support', 2 => '2 — Developing', 3 => '3 — Satisfactory', 4 => '4 — Good', 5 => '5 — Excellent'])->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['student', 'trait', 'session', 'term']))->columns([
            Tables\Columns\TextColumn::make('student.student_number')->label('Student')->searchable(),
            Tables\Columns\TextColumn::make('trait.name')->label('Trait'),
            Tables\Columns\TextColumn::make('session.name')->label('Session'),
            Tables\Columns\TextColumn::make('term.name')->label('Term'),
            Tables\Columns\TextColumn::make('rating')->numeric()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListAffectiveRatings::route('/'), 'create' => Pages\CreateAffectiveRating::route('/create'), 'edit' => Pages\EditAffectiveRating::route('/{record}/edit')]; }
}
