<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicProgrammeResource\Pages;
use App\Filament\Resources\AcademicProgrammeResource\RelationManagers;
use App\Models\AcademicProgramme;
use Filament\Forms;
use App\Filament\Support\MediaField;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcademicProgrammeResource extends AuthorizedResource
{
    protected static ?string $navigationGroup = 'Homepage & menus';
    protected static ?string $navigationLabel = 'Programmes';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 2;
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $model = AcademicProgramme::class;


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Programme')->schema([
                Forms\Components\TextInput::make('title')->label('Name')->required()->maxLength(120)->helperText('Example: Primary School, Secondary School, Science Programme.'),
                Forms\Components\Select::make('level')->label('Level')
                    ->options(['nursery' => 'Nursery', 'primary' => 'Primary', 'secondary' => 'Secondary', 'other' => 'Other'])->required()->native(false),
                Forms\Components\Textarea::make('intro')->label('Short description')->rows(4)->columnSpanFull(),
                Forms\Components\Repeater::make('approach')->label('Key points (optional)')->simple(
                    Forms\Components\TextInput::make('point')->required()->maxLength(200)
                )->addActionLabel('Add a point')->defaultItems(0)->columnSpanFull(),
                MediaField::image('image_id', 'Picture'),
                Forms\Components\Toggle::make('is_published')->label('Show on the website')->default(true),
            ])->columns(2),
            Forms\Components\Section::make('More options (optional)')->schema([
                Forms\Components\Textarea::make('placeholder_note')->label('Note shown on the programme page')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('seo_title')->label('Title for Google')->maxLength(70),
                Forms\Components\Textarea::make('seo_description')->label('Description for Google')->rows(2)->maxLength(160),
            ])->collapsed(),
            Forms\Components\Hidden::make('sort_order')->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image.path')->label('Picture')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Programme')->searchable(),
                Tables\Columns\TextColumn::make('level')->badge(),
                Tables\Columns\IconColumn::make('is_published')->label('Shown')->boolean(),
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
            'index' => Pages\ListAcademicProgrammes::route('/'),
            'create' => Pages\CreateAcademicProgramme::route('/create'),
            'edit' => Pages\EditAcademicProgramme::route('/{record}/edit'),
        ];
    }
}
