<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayConfigResource\Pages;
use App\Models\PaymentGatewayConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentGatewayConfigResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage payments';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Payment gateways';
    protected static ?string $navigationGroup = 'Fees & payments';
    protected static ?string $model = PaymentGatewayConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('display_name')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('driver')->disabled()->dehydrated(false),
            Forms\Components\Toggle::make('is_enabled')->label('Enable this provider')->helperText('Online checkout is available only when a supported provider has server-side credentials configured.'),
            Forms\Components\Placeholder::make('configuration_status')->label('Credential status')->content(fn (?PaymentGatewayConfig $record) => $record?->configuration_status === 'configured' ? 'Environment credentials configured' : 'Environment credentials not confirmed'),
            Forms\Components\Placeholder::make('capabilities')->label('Provider capability')->content(fn (?PaymentGatewayConfig $record) => $record?->supports_online_checkout ? 'Hosted online checkout' : 'Online checkout unavailable; POS/merchant product must be confirmed'),
            Forms\Components\Placeholder::make('secret_notice')->label('Credentials')->content('Secrets are never entered or stored in this admin screen. Configure them in the server environment and rotate them through the provider.')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('display_name')->label('Provider'),
            Tables\Columns\IconColumn::make('is_enabled')->boolean(),
            Tables\Columns\IconColumn::make('supports_online_checkout')->boolean()->label('Hosted checkout'),
            Tables\Columns\IconColumn::make('supports_refunds')->boolean(),
            Tables\Columns\TextColumn::make('configuration_status')->badge(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPaymentGatewayConfigs::route('/'), 'edit' => Pages\EditPaymentGatewayConfig::route('/{record}/edit')];
    }
}
