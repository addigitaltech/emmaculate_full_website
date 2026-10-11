<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage teachers';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Teachers';
    protected static ?string $navigationGroup = 'People';
    protected static ?string $model = Teacher::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->relationship('user', 'email', modifyQueryUsing: fn ($query) => $query->role('Teacher'))->searchable()->preload()->placeholder('No portal account linked'),
            Forms\Components\TextInput::make('staff_number')->maxLength(80)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('first_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('last_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('other_names')->maxLength(150),
            Forms\Components\TextInput::make('email')->email()->maxLength(190),
            Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
            Forms\Components\Select::make('gender')->options(['Female' => 'Female', 'Male' => 'Male', 'Not recorded' => 'Not recorded'])->placeholder('Not recorded'),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('user'))->columns([
            Tables\Columns\TextColumn::make('staff_number')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('first_name')->searchable(),
            Tables\Columns\TextColumn::make('last_name')->searchable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('user.email')->label('Portal account')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListTeachers::route('/'), 'create' => Pages\CreateTeacher::route('/create'), 'edit' => Pages\EditTeacher::route('/{record}/edit')]; }
}
