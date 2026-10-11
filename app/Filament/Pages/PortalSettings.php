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
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Portal & website switches';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Portal & website switches';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('Super Admin');
    }

    public function mount(): void
    {
        $s = SchoolSettings::query()->orderBy('id')->firstOrFail();
        $this->form->fill([
            'student_portal_enabled' => (bool) $s->student_portal_enabled,
            'parent_portal_enabled' => (bool) $s->parent_portal_enabled,
            'public_result_check_enabled' => (bool) $s->public_result_check_enabled,
            'payment_gate_results' => (bool) $s->payment_gate_results,
            'show_admin_link' => (bool) $s->show_admin_link,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Who can sign in')->description('Teachers and administrators are never affected by these switches.')->schema([
                Forms\Components\Toggle::make('student_portal_enabled')->label('Student portal')
                    ->helperText('On: students sign in with their admission number and surname. Off: students cannot sign in.'),
                Forms\Components\Toggle::make('parent_portal_enabled')->label('Parent portal')
                    ->helperText('On: parents sign in with their email and see their children. Off: parents cannot sign in.'),
                Forms\Components\Toggle::make('public_result_check_enabled')->label('"Check Result" page (no sign-in)')
                    ->helperText('On: anyone with an admission number and surname can view published results. Use this when the portals are off.'),
                Forms\Components\Toggle::make('payment_gate_results')->label('Hold results until school fees are paid')
                    ->helperText('On: a student with unpaid fees cannot open results.'),
            ])->columns(2),
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
        SchoolSettings::query()->orderBy('id')->firstOrFail()->fill([
            'student_portal_enabled' => (bool) ($d['student_portal_enabled'] ?? false),
            'parent_portal_enabled' => (bool) ($d['parent_portal_enabled'] ?? false),
            'public_result_check_enabled' => (bool) ($d['public_result_check_enabled'] ?? false),
            'payment_gate_results' => (bool) ($d['payment_gate_results'] ?? false),
            'show_admin_link' => (bool) ($d['show_admin_link'] ?? false),
        ])->save();

        Notification::make()->success()->title('Saved.')->send();
    }
}
