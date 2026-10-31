<?php

namespace App\Filament\Pages;

use App\Domain\Payments\PaymentReportBuilder;
use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\SchoolClass;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;

/** Every payment and every debt in one place, organised, filterable by dates or session, and printable. */
class PaymentHistory extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Fees & payments';
    protected static ?string $navigationLabel = 'Payment history & debts';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Payment history and who still owes';
    protected static ?string $slug = 'payment-history';
    protected static string $view = 'filament.pages.payment-history';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage payments')));
    }

    public function mount(): void
    {
        $this->form->fill(['status' => 'successful', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Choose what to see')->description('Pick a date range, or a whole session, then print or download.')->schema([
                Forms\Components\DatePicker::make('from')->label('From')->live(onBlur: true),
                Forms\Components\DatePicker::make('to')->label('To')->live(onBlur: true),
                Forms\Components\Select::make('session_id')->label('Whole session (ignores nothing: combine with dates if you like)')->options(fn () => AcademicSession::query()->orderByDesc('id')->pluck('name', 'id'))->placeholder('Any session')->live(),
                Forms\Components\Select::make('class_id')->label('Class')->options(fn () => SchoolClass::query()->orderBy('name')->pluck('name', 'id'))->placeholder('All classes')->live()->afterStateUpdated(fn (Set $set) => $set('arm_id', null)),
                Forms\Components\Select::make('arm_id')->label('Arm')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('class_id')))->orderBy('name')->pluck('name', 'id'))->placeholder('All arms')->live(),
                Forms\Components\Select::make('status')->label('Show')->options(['successful' => 'Verified payments', 'pending' => 'Awaiting verification', 'failed' => 'Failed', 'refunded' => 'Refunded', 'all' => 'Everything'])->default('successful')->required()->live(),
                Forms\Components\TextInput::make('q')->label('Search student name, Student ID or reference')->maxLength(80)->live(onBlur: true),
            ])->columns(3),
        ])->statePath('data');
    }

    /** The filters, cleaned so only valid values reach the report. */
    public function filters(): array
    {
        $data = $this->data ?? [];

        return array_filter([
            'from' => ! empty($data['from']) ? (string) $data['from'] : null,
            'to' => ! empty($data['to']) ? (string) $data['to'] : null,
            'session_id' => ! empty($data['session_id']) ? (int) $data['session_id'] : null,
            'class_id' => ! empty($data['class_id']) ? (int) $data['class_id'] : null,
            'arm_id' => ! empty($data['arm_id']) ? (int) $data['arm_id'] : null,
            'status' => in_array($data['status'] ?? 'successful', ['successful', 'pending', 'failed', 'refunded', 'all'], true) ? $data['status'] : 'successful',
            'q' => ! empty($data['q']) ? mb_substr(trim((string) $data['q']), 0, 80) : null,
        ], fn ($value) => $value !== null);
    }

    public function report(): array
    {
        return app(PaymentReportBuilder::class)->build($this->filters());
    }

    public function reportUrl(?string $download = null): string
    {
        return route('finance.report', $this->filters() + ($download ? ['download' => $download] : []));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign')->label('Assign fees to classes')->icon('heroicon-o-plus-circle')->color('gray')->url(fn () => AssignFees::getUrl()),
        ];
    }
}
