<?php

namespace App\Filament\Pages;

use App\Domain\Messaging\Audience;
use App\Mail\SchoolUpdateMail;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

/** Send a message to a group of people. Emails are queued and sent a few at a time. */
class EmailCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Admissions & messages';
    protected static ?string $navigationLabel = 'Send an email';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Send an email';
    protected static string $view = 'filament.pages.simple-form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage website content')));
    }

    public function mount(): void
    {
        $this->form->fill(['audience' => 'parents']);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Who should get it?')->schema([
                Forms\Components\Select::make('audience')->label('Send to')->options(Audience::OPTIONS)->required()->native(false)->live(),
                Forms\Components\Placeholder::make('count')->label('People who will receive it')
                    ->content(fn (Get $get): string => (string) count(Audience::recipients((string) ($get('audience') ?: 'parents')))),
            ])->columns(2),
            Forms\Components\Section::make('Your message')->schema([
                Forms\Components\TextInput::make('subject')->label('Subject')->required()->maxLength(150),
                Forms\Components\Textarea::make('message')->label('Message')->rows(8)->required()->maxLength(5000)
                    ->helperText('Press Enter twice to start a new paragraph.')->columnSpanFull(),
                Forms\Components\TextInput::make('button_label')->label('Button text (optional)')->maxLength(40),
                Forms\Components\TextInput::make('button_url')->label('Button link (optional)')->url()->maxLength(500),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Send email')->icon('heroicon-o-paper-airplane')->submit('save')
            ->requiresConfirmation()->modalHeading('Send this email?')->modalDescription('It cannot be recalled once sent.')];
    }

    public function save(): void
    {
        $d = $this->form->getState();
        $recipients = Audience::recipients((string) $d['audience']);
        if ($recipients === []) {
            Notification::make()->warning()->title('Nobody to send to')->body('No valid email addresses were found for that group.')->send();

            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient['email'])->send(new SchoolUpdateMail(
                subjectLine: (string) $d['subject'],
                heading: (string) $d['subject'],
                bodyText: (string) $d['message'],
                url: filled($d['button_url'] ?? null) ? (string) $d['button_url'] : null,
                buttonLabel: filled($d['button_url'] ?? null) ? ((string) ($d['button_label'] ?? '') ?: 'Open') : null,
                unsubscribeUrl: $recipient['unsubscribe'],
            ));
        }
        AuditLog::record(auth()->user(), 'email.broadcast', null, ['audience' => $d['audience'], 'recipients' => count($recipients)]);

        Notification::make()->success()->title('Email queued for '.count($recipients).' people')->body('Emails go out a few at a time, so large groups can take a little while.')->send();
        $this->form->fill(['audience' => $d['audience']]);
    }
}
