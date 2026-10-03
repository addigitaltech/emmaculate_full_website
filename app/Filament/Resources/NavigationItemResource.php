<?php

namespace App\Filament\Resources;

use App\Domain\Website\Support\SafePublicUrl;
use App\Filament\Resources\NavigationItemResource\Pages;
use App\Models\NavigationItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;

class NavigationItemResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $model = NavigationItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    public static function form(Form $form): Form
    {
        $safeUrlRule = function (string $attribute, $value, $fail): void {
            if (filled($value) && ! SafePublicUrl::allows((string) $value)) $fail('Use an internal path or an HTTPS link without credentials.');
        };
        return $form->schema([
            Forms\Components\Select::make('menu')->options(['main' => 'Main navigation'])->default('main')->required(),
            Forms\Components\Select::make('parent_id')->label('Parent item')->options(fn (Get $get, ?NavigationItem $record) => NavigationItem::query()->whereNull('parent_id')->where('menu', $get('menu') ?: 'main')->when($record, fn ($query) => $query->whereKeyNot($record->id))->orderBy('sort_order')->pluck('label', 'id'))->searchable()->placeholder('Top-level item'),
            Forms\Components\TextInput::make('label')->required()->maxLength(100),
            Forms\Components\TextInput::make('url')->required()->maxLength(2048)->rules([$safeUrlRule])->helperText('Use a local path such as /about or an HTTPS URL.'),
            Forms\Components\TextInput::make('description')->maxLength(200),
            Forms\Components\Toggle::make('is_visible')->default(true),
            Forms\Components\TextInput::make('sort_order')->integer()->minValue(0)->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('menu')->badge(),
            Tables\Columns\TextColumn::make('parent.label')->label('Parent'),
            Tables\Columns\TextColumn::make('label')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('url')->limit(40),
            Tables\Columns\IconColumn::make('is_visible')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->numeric()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListNavigationItems::route('/'), 'create' => Pages\CreateNavigationItem::route('/create'), 'edit' => Pages\EditNavigationItem::route('/{record}/edit')]; }
}
