<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage users and roles';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(180),
            Forms\Components\TextInput::make('email')->email()->required()->maxLength(180)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')->password()->revealable()->dehydrated(fn ($state) => filled($state))->required(fn (string $operation) => $operation === 'create')->minLength(12)->helperText('At least 12 characters; leave blank when editing to keep the current password.'),
            Forms\Components\Select::make('roles')->label('Roles')->relationship('roles', 'name')->multiple()->preload()->required()->minItems(1)->helperText('Grant only the school roles this account needs.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('roles.name')->badge()->separator(', '),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/'), 'create' => Pages\CreateUser::route('/create'), 'edit' => Pages\EditUser::route('/{record}/edit')];
    }
}
