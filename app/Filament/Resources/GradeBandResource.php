<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeBandResource\Pages;
use App\Models\GradeBand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class GradeBandResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage results';
    protected static ?string $navigationGroup = 'Results';
    protected static ?string $model = GradeBand::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('min_score')->numeric()->integer()->minValue(0)->maxValue(100)->required(),
            Forms\Components\TextInput::make('max_score')->numeric()->integer()->minValue(0)->maxValue(100)->required(),
            Forms\Components\TextInput::make('grade')->required()->maxLength(10),
            Forms\Components\TextInput::make('remark')->required()->maxLength(120),
            Forms\Components\TextInput::make('sort_order')->numeric()->integer()->minValue(0)->default(0)->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            Tables\Columns\TextColumn::make('min_score')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('max_score')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('grade')->searchable(),
            Tables\Columns\TextColumn::make('remark')->searchable(),
            Tables\Columns\TextColumn::make('sort_order')->numeric()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListGradeBands::route('/'), 'create' => Pages\CreateGradeBand::route('/create'), 'edit' => Pages\EditGradeBand::route('/{record}/edit')]; }
}
