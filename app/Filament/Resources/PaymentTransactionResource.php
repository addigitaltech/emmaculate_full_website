<?php

namespace App\Filament\Resources;

use App\Domain\Payments\PaymentRefundService;
use App\Filament\Resources\PaymentTransactionResource\Pages;
use App\Models\PaymentTransaction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentTransactionResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage payments';
    protected static ?string $navigationGroup = 'Payments';
    protected static bool $readOnly = true;
    protected static ?string $model = PaymentTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form { return $form->schema([]); }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('student'))->columns([
            Tables\Columns\TextColumn::make('student_label')->label('Student')->state(fn (PaymentTransaction $record) => $record->student?->fullName() ?? '—'),
            Tables\Columns\TextColumn::make('gateway')->badge(),
            Tables\Columns\TextColumn::make('reference')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('amount')->money('NGN')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('refund_status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('refund_provider_status')->toggleable(),
            Tables\Columns\TextColumn::make('refund_reference')->toggleable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('refund_requested_at')->dateTime()->toggleable(),
            Tables\Columns\TextColumn::make('refunded_at')->dateTime()->toggleable(),
        ])->actions([
            Tables\Actions\Action::make('requestFullRefund')->label('Request full refund')->icon('heroicon-o-arrow-uturn-left')->color('danger')->requiresConfirmation()
                ->modalHeading('Request a full provider refund?')
                ->modalDescription('This sends a full refund request to the payment provider. It cannot be automatically retried if the provider response is ambiguous. Check the provider dashboard before any manual reconciliation.')
                ->modalSubmitActionLabel('Request refund')
                ->visible(fn (PaymentTransaction $record) => (auth()->user()?->hasRole('Super Admin') || auth()->user()?->can('issue refunds')) && $record->status === 'successful' && $record->refund_status === 'none')
                ->action(function (PaymentTransaction $record): void {
                    app(PaymentRefundService::class)->requestFullRefund($record, auth()->user());
                    Notification::make()->success()->title('Provider refund request accepted')->body('The original payment remains recorded as successful until the provider confirms the refund outcome.')->send();
                }),
        ])->bulkActions([]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListPaymentTransactions::route('/')]; }
}
