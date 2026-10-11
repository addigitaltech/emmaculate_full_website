<?php

namespace App\Filament\Pages;

use App\Models\GradeBand;
use App\Models\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/** Marks split, pass mark and grades: set once, applied to every class, subject and term. */
class AssessmentGrading extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Academics setup';
    protected static ?string $navigationLabel = 'Assessment & grading';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Assessment & grading';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage academic structure') || $user->can('manage results')));
    }

    public function mount(): void
    {
        $s = SchoolSettings::query()->orderBy('id')->firstOrFail();
        $this->form->fill([
            'ca1_max_score' => (int) $s->ca1_max_score, 'ca2_max_score' => (int) $s->ca2_max_score,
            'ca3_max_score' => (int) $s->ca3_max_score, 'exam_max_score' => (int) $s->exam_max_score,
            'pass_percentage' => (int) $s->pass_percentage, 'highlight_fail_grade' => (bool) $s->highlight_fail_grade,
            'bands' => GradeBand::query()->orderBy('min_score')->get(['grade', 'min_score', 'max_score', 'remark'])->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('How marks are shared')
                ->description('Set once. It applies to every class, subject and term. Use 0 for a test you do not give. The four numbers must add up to 100.')
                ->schema([
                    Forms\Components\TextInput::make('ca1_max_score')->label('First test (CA 1)')->numeric()->integer()->minValue(0)->maxValue(100)->required()->live(onBlur: true),
                    Forms\Components\TextInput::make('ca2_max_score')->label('Second test (CA 2)')->numeric()->integer()->minValue(0)->maxValue(100)->required()->live(onBlur: true),
                    Forms\Components\TextInput::make('ca3_max_score')->label('Third test (CA 3)')->numeric()->integer()->minValue(0)->maxValue(100)->required()->live(onBlur: true),
                    Forms\Components\TextInput::make('exam_max_score')->label('Exam')->numeric()->integer()->minValue(0)->maxValue(100)->required()->live(onBlur: true),
                    Forms\Components\Placeholder::make('total')->label('Total')->content(fn (Get $get): string => (string) ((int) $get('ca1_max_score') + (int) $get('ca2_max_score') + (int) $get('ca3_max_score') + (int) $get('exam_max_score')).' / 100'),
                ])->columns(5),
            Forms\Components\Section::make('Pass mark')->schema([
                Forms\Components\TextInput::make('pass_percentage')->label('A subject is passed from (%)')->numeric()->integer()->minValue(0)->maxValue(100)->required(),
                Forms\Components\Toggle::make('highlight_fail_grade')->label('Show failed grades in red on reports'),
            ])->columns(2),
            Forms\Components\Section::make('Grades')
                ->description('The ranges must start at 0, follow each other with no gap or overlap, and end at 100.')
                ->schema([
                    Forms\Components\Repeater::make('bands')->label('')
                        ->schema([
                            Forms\Components\TextInput::make('grade')->label('Grade')->required()->maxLength(4),
                            Forms\Components\TextInput::make('min_score')->label('From')->numeric()->integer()->minValue(0)->maxValue(100)->required(),
                            Forms\Components\TextInput::make('max_score')->label('To')->numeric()->integer()->minValue(0)->maxValue(100)->required(),
                            Forms\Components\TextInput::make('remark')->label('Remark')->maxLength(40)->placeholder('Excellent'),
                        ])->columns(4)->addActionLabel('Add a grade')->defaultItems(0),
                ]),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Save changes')->submit('save')];
    }

    public function save(): void
    {
        $d = $this->form->getState();
        $total = (int) $d['ca1_max_score'] + (int) $d['ca2_max_score'] + (int) $d['ca3_max_score'] + (int) $d['exam_max_score'];
        if ($total !== 100) {
            Notification::make()->danger()->title('The marks must add up to 100')->body('They currently add up to '.$total.'.')->persistent()->send();

            return;
        }

        $bands = collect($d['bands'] ?? [])->map(fn ($band) => [
            'grade' => strtoupper(trim((string) $band['grade'])), 'min_score' => (int) $band['min_score'],
            'max_score' => (int) $band['max_score'], 'remark' => trim((string) ($band['remark'] ?? '')),
        ])->sortBy('min_score')->values();
        $error = $this->bandError($bands->all());
        if ($error !== null) {
            Notification::make()->danger()->title('Please fix the grades')->body($error)->persistent()->send();

            return;
        }

        DB::transaction(function () use ($d, $bands): void {
            SchoolSettings::query()->orderBy('id')->firstOrFail()->fill([
                'ca1_max_score' => (int) $d['ca1_max_score'], 'ca2_max_score' => (int) $d['ca2_max_score'],
                'ca3_max_score' => (int) $d['ca3_max_score'], 'exam_max_score' => (int) $d['exam_max_score'],
                'pass_percentage' => (int) $d['pass_percentage'], 'highlight_fail_grade' => (bool) ($d['highlight_fail_grade'] ?? false),
            ])->save();
            GradeBand::query()->delete();
            foreach ($bands as $index => $band) {
                GradeBand::query()->create($band + ['sort_order' => $index + 1]);
            }
        });

        Notification::make()->success()->title('Saved. It applies to every class.')->send();
    }

    /** @param array<int, array{grade: string, min_score: int, max_score: int, remark: string}> $bands */
    private function bandError(array $bands): ?string
    {
        if ($bands === []) {
            return 'Add at least one grade.';
        }
        $next = 0;
        $seen = [];
        foreach ($bands as $band) {
            if ($band['grade'] === '' || isset($seen[$band['grade']])) {
                return 'Every grade needs a different, non-empty name.';
            }
            $seen[$band['grade']] = true;
            if ($band['min_score'] !== $next) {
                return 'The ranges must follow each other with no gap or overlap. Expected the next range to start at '.$next.'.';
            }
            if ($band['max_score'] < $band['min_score']) {
                return 'In grade '.$band['grade'].', "To" cannot be smaller than "From".';
            }
            $next = $band['max_score'] + 1;
        }

        return $next === 101 ? null : 'The last range must end at 100.';
    }
}
