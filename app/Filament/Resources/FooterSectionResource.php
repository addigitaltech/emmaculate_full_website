<?php

namespace App\Filament\Resources;

use App\Domain\Website\Support\SafePublicUrl;
use App\Filament\Resources\FooterSectionResource\Pages;
use App\Models\FooterSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class FooterSectionResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Footer';
    protected static ?string $navigationGroup = 'Homepage & menus';
    protected static ?string $model = FooterSection::class;
    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';
    public static function form(Form $form): Form
    {
        $safeUrlRule = fn () => function (string $attribute, $value, $fail): void { if (filled($value) && ! SafePublicUrl::allows((string) $value)) $fail('Use an internal path or an HTTPS link without credentials.'); };
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(100),
            Forms\Components\Repeater::make('links')->schema([
                Forms\Components\TextInput::make('label')->required()->maxLength(100),
                Forms\Components\TextInput::make('url')->required()->maxLength(2048)->rules([$safeUrlRule]),
            ])->defaultItems(0)->columns(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_visible')->default(true),
            Forms\Components\TextInput::make('sort_order')->integer()->minValue(0)->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('links')->state(fn (FooterSection $record) => count($record->links ?? []).' links'),
            Tables\Columns\IconColumn::make('is_visible')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->numeric()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }
    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListFooterSections::route('/'), 'create' => Pages\CreateFooterSection::route('/create'), 'edit' => Pages\EditFooterSection::route('/{record}/edit')]; }
}
