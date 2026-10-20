<?php

namespace App\Filament\Pages;

use App\Domain\Results\Services\PinSlipService;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

/** Print student PIN slips in bulk: one class, one arm, or the whole school. */
class StudentPinSlips extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Students & staff';
    protected static ?string $navigationLabel = 'PIN slips';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Print student PIN slips';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage students')));
    }

    public function mount(): void
    {
        $this->form->fill(['scope' => 'class', 'which' => 'without_pin']);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Who needs a PIN slip?')->description('Each slip carries the student\'s Student ID and a new 6-digit PIN. PINs are shown only on the printed slips and cannot be looked up later.')->schema([
                Forms\Components\Select::make('scope')->label('Print for')->options(['class' => 'One class (optionally one arm)', 'school' => 'The whole school (one PDF per class, in a ZIP)'])->required()->live(),
                Forms\Components\Select::make('which')->label('Which students')->options([
                    'without_pin' => 'Only students who have no PIN yet (earlier slips stay valid)',
                    'all' => 'Every active student (replaces all existing PINs; old slips stop working)',
                ])->required(),
                Forms\Components\Select::make('school_class_id')->label('Class')->options(fn () => SchoolClass::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()
                    ->required(fn (Get $get) => $get('scope') === 'class')->visible(fn (Get $get) => $get('scope') === 'class')->live()->afterStateUpdated(fn (Set $set) => $set('arm_id', null)),
                Forms\Components\Select::make('arm_id')->label('Arm (optional)')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->orderBy('name')->pluck('name', 'id'))->searchable()
                    ->visible(fn (Get $get) => $get('scope') === 'class'),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('generate')->label('Generate PIN slips')->icon('heroicon-o-printer')->requiresConfirmation()
            ->modalDescription('New PINs will be created for the matching students and a printable file will download.')->submit('save')];
    }

    /** The shared simple-form view submits to save(); here that means "generate the slips". */
    public function save()
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();

        $query = Student::query()->with(['schoolClass', 'arm'])->where('status', 'active');
        if (($data['scope'] ?? 'class') === 'class') {
            $query->where('school_class_id', $data['school_class_id'])->when($data['arm_id'] ?? null, fn ($q, $arm) => $q->where('arm_id', $arm));
        }
        if (($data['which'] ?? 'without_pin') === 'without_pin') {
            $query->whereNull('pin_hash');
        }
        $students = $query->orderBy('last_name')->orderBy('first_name')->get();

        if ($students->isEmpty()) {
            Notification::make()->title('No matching students')->body('Everyone in that selection already has a PIN, or there are no active students.')->warning()->send();

            return null;
        }

        $service = app(PinSlipService::class);
        if ($data['scope'] === 'school') {
            return $service->downloadSchoolZip($students);
        }

        $className = SchoolClass::query()->whereKey($data['school_class_id'])->value('name') ?? 'class';

        return $service->download($students, 'pin-slips-'.Str::slug($className).(($data['arm_id'] ?? null) ? '-'.Str::slug((string) Arm::query()->whereKey($data['arm_id'])->value('name')) : ''));
    }
}
