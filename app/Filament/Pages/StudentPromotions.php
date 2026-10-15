<?php

namespace App\Filament\Pages;

use App\Domain\Results\Services\PromotionService;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\StudentPromotion;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentPromotions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Results';
    protected static ?string $navigationLabel = 'Student promotions';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Student promotions';
    protected static string $view = 'filament.pages.student-promotions';

    public ?array $data = [];
    public array $manualTargets = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage students')));
    }

    public function mount(): void
    {
        $this->form->fill([
            'source_session_id' => AcademicSession::query()->where('is_active', true)->value('id'),
            'destination_session_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Choose the academic transition')
                    ->description('Select the completed source session and the destination session. Automatic mode applies configured rules only after you launch this transition.')
                    ->schema([
                        Select::make('source_session_id')
                            ->label('Source session')
                            ->options(fn (): array => AcademicSession::query()->orderByDesc('id')->pluck('name', 'id')->all())
                            ->required()->live(),
                        Select::make('destination_session_id')
                            ->label('Destination session')
                            ->options(fn (Get $get): array => AcademicSession::query()
                                ->when($get('source_session_id'), fn ($query, $sourceId) => $query->whereKeyNot($sourceId))
                                ->orderByDesc('id')->pluck('name', 'id')->all())
                            ->required(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function launch(): void
    {
        $state = $this->form->getState();
        $source = AcademicSession::query()->findOrFail($state['source_session_id']);
        $destination = AcademicSession::query()->findOrFail($state['destination_session_id']);
        $actor = auth()->user();
        if (! $actor instanceof User) {
            abort(403);
        }

        $records = app(PromotionService::class)->processTransition($source, $destination, $actor);
        $created = $records->filter(fn (StudentPromotion $record): bool => $record->wasRecentlyCreated);
        $promoted = $created->where('decision_status', 'promoted')->count();
        $review = $created->where('decision_status', 'review')->count();
        $alreadyProcessed = $records->count() - $created->count();
        $mode = SchoolSettings::query()->orderBy('id')->value('promotion_mode') ?: 'manual';

        Notification::make()->success()
            ->title($mode === 'automatic' ? 'Automatic promotion batch complete' : 'Review list prepared')
            ->body($promoted.' newly promoted; '.$review.' flagged for review; '.$alreadyProcessed.' already processed.')
            ->send();
    }

    /** @return Collection<int, StudentPromotion> */
    public function records(): Collection
    {
        $sourceId = $this->data['source_session_id'] ?? null;
        $destinationId = $this->data['destination_session_id'] ?? null;
        if (! $sourceId || ! $destinationId || (int) $sourceId === (int) $destinationId) {
            return collect();
        }

        $records = StudentPromotion::query()
            ->with(['student', 'sourceClass', 'sourceArm', 'destinationClass', 'destinationArm'])
            ->where('source_session_id', $sourceId)
            ->where('destination_session_id', $destinationId)
            ->orderByRaw("CASE decision_status WHEN 'review' THEN 0 WHEN 'promoted' THEN 1 ELSE 2 END")
            ->orderBy('student_id')->get();

        foreach ($records as $record) {
            $this->manualTargets[$record->id] ??= [
                'class_id' => $record->destination_class_id,
                'arm_id' => $record->destination_arm_id,
            ];
        }

        return $records;
    }

    /** @return Collection<int, SchoolClass> */
    public function activeClasses(): Collection
    {
        return SchoolClass::query()->where('is_active', true)->with('arms')->orderBy('name')->get();
    }

    public function updatedManualTargets(mixed $value, string $key): void
    {
        if (str_ends_with($key, '.class_id')) {
            $recordId = explode('.', $key, 2)[0];
            $this->manualTargets[$recordId]['arm_id'] = null;
        }
    }

    public function decide(int $promotionId, string $decision): void
    {
        $record = StudentPromotion::query()->findOrFail($promotionId);
        $sourceId = (int) ($this->data['source_session_id'] ?? 0);
        $destinationId = (int) ($this->data['destination_session_id'] ?? 0);
        if ((int) $record->source_session_id !== $sourceId || (int) $record->destination_session_id !== $destinationId) {
            abort(404);
        }

        $actor = auth()->user();
        if (! $actor instanceof User) {
            abort(403);
        }
        $target = $this->manualTargets[$promotionId] ?? [];
        $destinationClassId = $decision === 'promote'
            ? (int) ($target['class_id'] ?? $record->destination_class_id ?? 0) ?: null
            : null;
        $destinationArmId = $decision === 'promote'
            ? (int) ($target['arm_id'] ?? $record->destination_arm_id ?? 0) ?: null
            : null;

        app(PromotionService::class)->decide($record, $actor, $decision, $destinationClassId, $destinationArmId);
        Notification::make()->success()->title($decision === 'promote' ? 'Student promoted' : 'Student retained')->send();
    }

    public function promotionMode(): string
    {
        return (string) (SchoolSettings::query()->orderBy('id')->value('promotion_mode') ?: 'manual');
    }

    public function promotionRulesSummary(): string
    {
        $settings = SchoolSettings::query()->orderBy('id')->first();
        if (! $settings || $settings->promotion_mode !== 'automatic') {
            return 'Manual mode: the batch creates a review list and does not change student assignments.';
        }

        return 'Automatic mode: students must meet an average of '.$settings->promotion_min_average
            .' and pass at least '.$settings->promotion_min_subjects_passed.' subjects at the school pass mark of '
            .$settings->pass_percentage.'%. Students with incomplete records or no configured class path remain in review.';
    }
}
