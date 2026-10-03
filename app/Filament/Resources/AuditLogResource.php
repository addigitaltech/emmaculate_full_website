<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Filament\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'view audit logs';
    protected static ?string $navigationGroup = 'System';
    protected static bool $readOnly = true;
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('actor_id')
                    ->numeric(),
                Forms\Components\TextInput::make('event')
                    ->required(),
                Forms\Components\TextInput::make('subject_type'),
                Forms\Components\TextInput::make('subject_id'),
                Forms\Components\TextInput::make('ip_address'),
                Forms\Components\TextInput::make('user_agent'),
                Forms\Components\Textarea::make('safe_metadata')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('actor_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListAuditLogs::route('/'),
            'create' => Pages\CreateAuditLog::route('/create'),
            'edit' => Pages\EditAuditLog::route('/{record}/edit'),
        ];
    }
}
