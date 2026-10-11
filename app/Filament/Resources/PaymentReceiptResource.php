<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentReceiptResource\Pages;
use App\Filament\Resources\PaymentReceiptResource\RelationManagers;
use App\Models\PaymentReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\AuthorizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentReceiptResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage payments';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 95;
    protected static ?string $navigationLabel = 'Receipts';
    protected static ?string $navigationGroup = 'Fees & payments';
    protected static bool $readOnly = true;
    protected static ?string $model = PaymentReceipt::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment_transaction_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('receipt_number')
                    ->required(),
                Forms\Components\TextInput::make('storage_path'),
                Forms\Components\DateTimePicker::make('issued_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_transaction_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('storage_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issued_at')
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
            'index' => Pages\ListPaymentReceipts::route('/'),
            'create' => Pages\CreatePaymentReceipt::route('/create'),
            'edit' => Pages\EditPaymentReceipt::route('/{record}/edit'),
        ];
    }
}
