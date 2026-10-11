<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentEventResource\Pages;
use App\Filament\Resources\PaymentEventResource\RelationManagers;
use App\Models\PaymentEvent;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentEventResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage payments';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 94;
    protected static ?string $navigationLabel = 'Payment events';
    protected static ?string $navigationGroup = 'Fees & payments';
    protected static bool $readOnly = true;
    protected static ?string $model = PaymentEvent::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment_transaction_id')
                    ->numeric(),
                Forms\Components\TextInput::make('gateway')
                    ->required(),
                Forms\Components\TextInput::make('provider_event_id'),
                Forms\Components\TextInput::make('event_type')
                    ->required(),
                Forms\Components\TextInput::make('payload_hash')
                    ->required(),
                Forms\Components\TextInput::make('processing_status')
                    ->required(),
                Forms\Components\Textarea::make('failure_reason')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('processed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_transaction_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider_event_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payload_hash')
                    ->searchable(),
                Tables\Columns\TextColumn::make('processing_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('processed_at')
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
            'index' => Pages\ListPaymentEvents::route('/'),
            'create' => Pages\CreatePaymentEvent::route('/create'),
            'edit' => Pages\EditPaymentEvent::route('/{record}/edit'),
        ];
    }
}
