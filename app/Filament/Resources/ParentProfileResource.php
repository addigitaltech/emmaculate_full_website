<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentProfileResource\Pages;
use App\Models\ParentProfile;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ParentProfileResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage students';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Parents';
    protected static ?string $navigationGroup = 'People';
    protected static ?string $model = ParentProfile::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('full_name')->required()->maxLength(180),
            Forms\Components\Select::make('user_id')->relationship('user', 'email', modifyQueryUsing: fn ($query) => $query->role('Parent'))->searchable()->preload()->placeholder('No portal account linked'),
            Forms\Components\TextInput::make('email')->email()->maxLength(190),
            Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
            Forms\Components\Select::make('students')->label('Linked children')->relationship('students', 'student_number')->multiple()->searchable()->preload()->getOptionLabelFromRecordUsing(fn (Student $record) => $record->fullName().' · '.$record->student_number)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['user', 'students']))->columns([
            Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('user.email')->label('Portal account'),
            Tables\Columns\TextColumn::make('students.student_number')->label('Linked children')->badge()->separator(', '),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('phone')->toggleable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListParentProfiles::route('/'), 'create' => Pages\CreateParentProfile::route('/create'), 'edit' => Pages\EditParentProfile::route('/{record}/edit')]; }
}
