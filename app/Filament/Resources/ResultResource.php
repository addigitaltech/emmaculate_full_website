<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResultResource\Pages;
use App\Filament\Resources\ResultResource\RelationManagers;
use App\Models\Result;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResultResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage results';
    protected static ?string $navigationGroup = 'Results';
    protected static bool $readOnly = true;
    protected static ?string $model = Result::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('subject_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('teacher_id')
                    ->numeric(),
                Forms\Components\TextInput::make('school_class_id')
                    ->numeric(),
                Forms\Components\TextInput::make('arm_id')
                    ->numeric(),
                Forms\Components\TextInput::make('academic_session_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('academic_term_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ca1_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('ca2_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('ca3_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('exam_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_offered')
                    ->required(),
                Forms\Components\TextInput::make('grade'),
                Forms\Components\TextInput::make('remark'),
                Forms\Components\TextInput::make('teacher_remark'),
                Forms\Components\TextInput::make('principal_remark'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('published_by')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('published_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('school_class_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('arm_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academic_session_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academic_term_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ca1_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ca2_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ca3_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_offered')
                    ->boolean(),
                Tables\Columns\TextColumn::make('grade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->searchable(),
                Tables\Columns\TextColumn::make('teacher_remark')
                    ->searchable(),
                Tables\Columns\TextColumn::make('principal_remark')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('published_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResults::route('/'),
            'create' => Pages\CreateResult::route('/create'),
            'edit' => Pages\EditResult::route('/{record}/edit'),
        ];
    }
}
