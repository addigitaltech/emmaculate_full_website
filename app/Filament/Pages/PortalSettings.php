<?php

namespace App\Filament\Pages;

use App\Models\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** Super Admin switches: who gets a portal, the public result check, the admin link in the footer. */
class PortalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Portal & website switches';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Portal & website switches';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) (auth()->user() && (auth()->user()->hasRole('Super Admin') || auth()->user()->can('manage students')));
    }

    public function mount(): void
    {
        $s = SchoolSettings::query()->orderBy('id')->firstOrFail();
        $this->form->fill([
            'student_portal_enabled' => (bool) $s->student_portal_enabled,
            'parent_portal_enabled' => (bool) $s->parent_portal_enabled,
            'staff_portal_enabled' => (bool) $s->staff_portal_enabled,
            'admission_portal_enabled' => (bool) $s->admission_portal_enabled,
            'public_result_check_enabled' => (bool) $s->public_result_check_enabled,
            'payment_gate_results' => (bool) $s->payment_gate_results,
            'payment_mode' => $s->payment_mode === 'full' ? 'full' : 'installment',
            'installment_min_amount' => (float) $s->installment_min_amount,
            'finance_notify_emails' => $s->finance_notify_emails,
            'finance_notify_phones' => $s->finance_notify_phones,
            'sms_payer' => (bool) $s->sms_payer,
            'require_pin_student_login' => (bool) $s->require_pin_student_login,
            'require_pin_result_check' => (bool) $s->require_pin_result_check,
            'show_admin_link' => (bool) $s->show_admin_link,
            'promotion_mode' => $s->promotion_mode ?? 'manual',
            'promotion_min_average' => $s->promotion_min_average,
            'promotion_min_subjects_passed' => $s->promotion_min_subjects_passed,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Portals on the website')->description('Switch a portal off and it disappears from the website menus, home page and footer straight away. Administrators can always use /admin.')->schema([
                Forms\Components\Toggle::make('admission_portal_enabled')->label('Admission portal')
                    ->helperText('On: the Apply online form and the Admission Portal link are shown. Off: they are hidden.'),
                Forms\Components\Toggle::make('staff_portal_enabled')->label('Staff portal (teachers)')
                    ->helperText('On: teachers sign in at the Staff Portal. Off: teachers cannot sign in.'),
                Forms\Components\Toggle::make('student_portal_enabled')->label('Student portal')
                    ->helperText('On: students sign in with their Student ID and surname. Off: students cannot sign in.'),
                Forms\Components\Toggle::make('parent_portal_enabled')->label('Parent portal')
                    ->helperText('On: parents sign in with their email and see their children. Off: parents cannot sign in.'),
                Forms\Components\Toggle::make('public_result_check_enabled')->label('Results portal: "Check Result" (no sign-in)')
                    ->helperText('On: a Results Portal link appears, where anyone with a Student ID and surname can view published results. Off: the link and page are hidden.'),
                Forms\Components\Toggle::make('require_pin_student_login')->label('Require PIN to sign in (student portal)')
                    ->helperText('On: students must also type the 6-digit PIN from their PIN slip. Off: Student ID and surname are enough. Issue PIN slips first (Students > Issue PIN slips), then switch this on.'),
                Forms\Components\Toggle::make('require_pin_result_check')->label('Require PIN to check results (Check Result page)')
                    ->helperText('On: the public Check Result page also asks for the student’s 6-digit PIN. Off: Student ID and surname are enough.'),
                Forms\Components\Select::make('payment_mode')->label('How can fees be paid?')->options([
                    'installment' => 'Installments allowed (pay any amount from the minimum up to the balance)',
                    'full' => 'Full payment only (all of a student\'s unpaid fees are added together and paid at once)',
                ])->required()->native(false),
                Forms\Components\TextInput::make('installment_min_amount')->label('Smallest installment (naira)')->numeric()->minValue(0)->maxValue(100000000)->required()
                    ->helperText('Used when installments are allowed. If a balance is smaller than this, the balance can be paid.'),
                Forms\Components\Textarea::make('finance_notify_emails')->label('Email these people when a payment is made')->rows(2)->maxLength(500)
                    ->helperText('Separate addresses with commas. Leave empty to use the school email. Finance Admin users are always included.'),
                Forms\Components\Textarea::make('finance_notify_phones')->label('Text these phone numbers when a payment is made')->rows(2)->maxLength(300)
                    ->helperText('Separate numbers with commas, e.g. 08031234567. Needs SMS to be set up (docs/PAYMENTS.md). Leave empty to use the school phone.'),
                Forms\Components\Toggle::make('sms_payer')->label('Also text the parent a confirmation')
                    ->helperText('Sends to the parent phone numbers on file. Needs SMS to be set up.'),
                Forms\Components\Toggle::make('payment_gate_results')->label('Hold results until school fees are paid')
                    ->helperText('On: a student with unpaid fees cannot open results.'),
            ])->columns(2)->visible(fn (): bool => (bool) auth()->user()?->hasRole('Super Admin')),
            Forms\Components\Section::make('Student promotion')
                ->description('Automatic decisions are applied only when an authorized administrator launches a source-to-destination academic-session transition. Unfinished or ineligible records are flagged for review.')
                ->schema([
                    Forms\Components\Select::make('promotion_mode')->label('Promotion mode')
                        ->options(['manual' => 'Manual review', 'automatic' => 'Automatic by configured rules'])
                        ->default('manual')->required(),
                    Forms\Components\TextInput::make('promotion_min_average')->label('Minimum third-term cumulative average')
                        ->numeric()->minValue(0)->maxValue(100)->step('0.01')
                        ->required(fn (Forms\Get $get): bool => $get('promotion_mode') === 'automatic')
                        ->helperText('Average of the student’s cumulative averages across offered subjects; configure a value before selecting Automatic.'),
                    Forms\Components\TextInput::make('promotion_min_subjects_passed')->label('Minimum passed subjects')
                        ->numeric()->integer()->minValue(0)->maxValue(1000)
                        ->required(fn (Forms\Get $get): bool => $get('promotion_mode') === 'automatic')
                        ->helperText('A subject passes at the school’s configured pass percentage.'),
                ])->columns(2)->visible(fn (): bool => (bool) auth()->user()?->can('manage students')),
            Forms\Components\Section::make('Website')->schema([
                Forms\Components\Toggle::make('show_admin_link')->label('Show "Admin login" in the website footer')
                    ->helperText('Turn off to hide the shortcut. The admin login still works at /admin.'),
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
        $fields = [
            'promotion_mode' => $d['promotion_mode'] ?? 'manual',
            'promotion_min_average' => filled($d['promotion_min_average'] ?? null) ? $d['promotion_min_average'] : null,
            'promotion_min_subjects_passed' => filled($d['promotion_min_subjects_passed'] ?? null) ? $d['promotion_min_subjects_passed'] : null,
        ];
        if (auth()->user()?->hasRole('Super Admin')) {
            $fields += [
            'student_portal_enabled' => (bool) ($d['student_portal_enabled'] ?? false),
            'parent_portal_enabled' => (bool) ($d['parent_portal_enabled'] ?? false),
            'staff_portal_enabled' => (bool) ($d['staff_portal_enabled'] ?? false),
            'admission_portal_enabled' => (bool) ($d['admission_portal_enabled'] ?? false),
            'public_result_check_enabled' => (bool) ($d['public_result_check_enabled'] ?? false),
            'payment_gate_results' => (bool) ($d['payment_gate_results'] ?? false),
            'payment_mode' => ($d['payment_mode'] ?? 'installment') === 'full' ? 'full' : 'installment',
            'installment_min_amount' => max(0, min(100000000, (float) ($d['installment_min_amount'] ?? 1000))),
            'finance_notify_emails' => self::cleanList($d['finance_notify_emails'] ?? null, fn ($item) => filter_var($item, FILTER_VALIDATE_EMAIL) ? strtolower($item) : null),
            'finance_notify_phones' => self::cleanList($d['finance_notify_phones'] ?? null, fn ($item) => \App\Domain\Notifications\SmsService::normalise($item) ? preg_replace('/[^\d+]/', '', $item) : null),
            'sms_payer' => (bool) ($d['sms_payer'] ?? false),
            'require_pin_student_login' => (bool) ($d['require_pin_student_login'] ?? false),
            'require_pin_result_check' => (bool) ($d['require_pin_result_check'] ?? false),
            'show_admin_link' => (bool) ($d['show_admin_link'] ?? false),
            ];
        }
        SchoolSettings::query()->orderBy('id')->firstOrFail()->fill($fields)->save();

        Notification::make()->success()->title('Saved.')->send();
    }

    /** Keep only the valid items of a comma-separated list (emails or phone numbers) typed by the administrator. */
    private static function cleanList(?string $value, callable $accept): ?string
    {
        $items = collect(preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($item) => $accept(trim($item)))->filter()->unique()->values();

        return $items->isEmpty() ? null : $items->implode(', ');
    }
}
