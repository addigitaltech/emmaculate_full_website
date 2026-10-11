<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadershipProfileResource\Pages;
use App\Filament\Resources\LeadershipProfileResource\RelationManagers;
use App\Models\LeadershipProfile;
use Filament\Forms;
use App\Filament\Support\MediaField;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadershipProfileResource extends AuthorizedResource
{
    protected static ?string $navigationGroup = 'School profile';
    protected static ?string $navigationLabel = 'School leadership';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 2;
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $model = LeadershipProfile::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Leader')->schema([
                Forms\Components\TextInput::make('name')->label('Full name')->required()->maxLength(120),
                Forms\Components\TextInput::make('title')->label('Position')->required()->maxLength(120)->helperText('Example: Principal, Proprietor, Vice Principal.'),
                MediaField::image('photo_id', 'Photo'),
                Forms\Components\Toggle::make('is_visible')->label('Show on the website')->default(true),
                Forms\Components\Textarea::make('biography')->label('About this person (short)')->rows(4)->columnSpanFull(),
                Forms\Components\TextInput::make('qualifications')->label('Qualifications (optional)')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Hidden::make('sort_order')->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo.path')->label('Photo')->disk('public')->circular(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Position'),
                Tables\Columns\IconColumn::make('is_visible')->label('Shown')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([]);
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
            'index' => Pages\ListLeadershipProfiles::route('/'),
            'create' => Pages\CreateLeadershipProfile::route('/create'),
            'edit' => Pages\EditLeadershipProfile::route('/{record}/edit'),
        ];
    }
}
