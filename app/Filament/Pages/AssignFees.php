<?php

namespace App\Filament\Pages;

use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\Arm;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** Add a fee once and charge it to every student of chosen classes (and arm), instead of student by student. */
class AssignFees extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationGroup = 'Fees & payments';
    protected static ?string $navigationLabel = 'Assign fees to classes';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Assign a fee to classes and arms';
    protected static ?string $slug = 'assign-fees';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage payments')));
    }

    public function mount(): void
    {
        $this->form->fill(['academic_session_id' => AcademicSession::query()->where('is_active', true)->value('id')]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The fee')->description('Students who already have this fee for the same session and term are skipped, so pressing the button twice never charges anyone twice.')->schema([
                Forms\Components\Select::make('fee_type_id')->label('Type of fee')->options(fn () => FeeType::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->required()
                    ->createOptionForm([Forms\Components\TextInput::make('name')->required()->maxLength(120)])
                    ->createOptionUsing(fn (array $data) => FeeType::query()->create(['name' => $data['name'], 'is_active' => true])->getKey()),
                Forms\Components\TextInput::make('amount_due')->label('Amount for each student (naira)')->numeric()->minValue(0.01)->maxValue(100000000)->required()->prefix('₦'),
                Forms\Components\DatePicker::make('due_date')->label('Pay by'),
                Forms\Components\Select::make('academic_session_id')->label('Session')->options(fn () => AcademicSession::query()->orderByDesc('id')->pluck('name', 'id'))->required()->live()->afterStateUpdated(fn (Set $set) => $set('academic_term_id', null)),
                Forms\Components\Select::make('academic_term_id')->label('Term (optional)')->options(fn (Get $get) => AcademicTerm::query()->where('academic_session_id', $get('academic_session_id'))->orderBy('sequence')->pluck('name', 'id'))->placeholder('Whole session'),
                Forms\Components\Textarea::make('description')->label('Note shown to parents (optional)')->rows(2)->maxLength(500)->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Who pays it')->schema([
                Forms\Components\Select::make('class_ids')->label('Classes')->multiple()->options(fn () => SchoolClass::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->required()->live()->afterStateUpdated(fn (Set $set) => $set('arm_id', null)),
                Forms\Components\Select::make('arm_id')->label('Only this arm (optional)')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereIn('school_classes.id', (array) $get('class_ids')))->orderBy('name')->pluck('name', 'id'))->placeholder('All arms'),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('assign')->label('Charge this fee')->requiresConfirmation()
            ->modalDescription('Every active student in the chosen classes and arm is charged this fee now.')->submit('save')];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();

        $students = Student::query()->where('status', 'active')
            ->whereIn('school_class_id', array_map('intval', (array) $data['class_ids']))
            ->when(! empty($data['arm_id']), fn ($query) => $query->where('arm_id', (int) $data['arm_id']))
            ->get(['id']);

        $created = 0;
        $skipped = 0;
        foreach ($students as $student) {
            $exists = StudentFeeAssignment::query()
                ->where('student_id', $student->id)->where('fee_type_id', (int) $data['fee_type_id'])
                ->where('academic_session_id', (int) $data['academic_session_id'])
                ->when(! empty($data['academic_term_id']), fn ($query) => $query->where('academic_term_id', (int) $data['academic_term_id']), fn ($query) => $query->whereNull('academic_term_id'))
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }
            StudentFeeAssignment::query()->create([
                'student_id' => $student->id,
                'fee_type_id' => (int) $data['fee_type_id'],
                'academic_session_id' => (int) $data['academic_session_id'],
                'academic_term_id' => ! empty($data['academic_term_id']) ? (int) $data['academic_term_id'] : null,
                'amount_due' => round((float) $data['amount_due'], 2),
                'currency' => 'NGN',
                'due_date' => $data['due_date'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
            $created++;
        }

        Notification::make()->success()->title($created.' student(s) charged')->body($skipped ? $skipped.' already had this fee and were left alone.' : 'Parents and students can now see it in their portal.')->send();
    }
}
