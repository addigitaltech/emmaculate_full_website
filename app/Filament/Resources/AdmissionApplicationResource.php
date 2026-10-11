<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdmissionApplicationResource\Pages;
use App\Filament\Resources\AdmissionApplicationResource\RelationManagers;
use App\Models\AdmissionApplication;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdmissionApplicationResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Applications';
    protected static ?string $navigationGroup = 'Admissions & messages';
    protected static ?string $model = AdmissionApplication::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required(),
                Forms\Components\TextInput::make('applicant_name')
                    ->required(),
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\TextInput::make('applying_for')
                    ->required(),
                Forms\Components\TextInput::make('guardian_name')
                    ->required(),
                Forms\Components\TextInput::make('guardian_email')
                    ->email(),
                Forms\Components\TextInput::make('guardian_phone')
                    ->tel()
                    ->required(),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('metadata')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('submitted_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('applicant_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applying_for')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guardian_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guardian_email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guardian_phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('submitted_at')
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
            'index' => Pages\ListAdmissionApplications::route('/'),
            'create' => Pages\CreateAdmissionApplication::route('/create'),
            'edit' => Pages\EditAdmissionApplication::route('/{record}/edit'),
        ];
    }
}
