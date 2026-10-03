<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortalLinkResource\Pages;
use App\Models\PortalLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class PortalLinkResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage website content';
    protected static ?string $navigationGroup = 'Website';
    protected static ?string $navigationLabel = 'Portal links';
    protected static ?string $model = PortalLink::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-top-right-on-square';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')->required()->maxLength(100)->unique(ignoreRecord: true)->helperText('Stable unique identifier for this portal card.'),
            Forms\Components\TextInput::make('title')->required()->maxLength(160),
            Forms\Components\Textarea::make('description')->rows(3)->maxLength(600)->columnSpanFull(),
            Forms\Components\TextInput::make('url')->label('Destination')->maxLength(2048)->helperText('Use a same-site path such as /login or an HTTPS URL. Live links require a destination.'),
            Forms\Components\Select::make('status')->options(['live' => 'Live', 'coming_soon' => 'Coming Soon', 'disabled' => 'Hidden'])->required()->default('live'),
            Forms\Components\TextInput::make('sort_order')->numeric()->minValue(0)->required()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('url')->label('Destination')->limit(50),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) { 'live' => 'success', 'coming_soon' => 'warning', default => 'gray' }),
            Tables\Columns\TextColumn::make('sort_order')->numeric()->sortable(),
        ])->defaultSort('sort_order')->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortalLinks::route('/'),
            'create' => Pages\CreatePortalLink::route('/create'),
            'edit' => Pages\EditPortalLink::route('/{record}/edit'),
        ];
    }
}
