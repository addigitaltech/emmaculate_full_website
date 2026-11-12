<?php

namespace App\Filament\Pages;

use App\Domain\Backups\BackupArchiveService;
use App\Models\AcademicTerm;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DataBackup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Results & people backup';
    protected static ?int $navigationSort = 9;
    protected static ?string $title = 'Results and people backup';
    protected static ?string $slug = 'data-backup';
    protected static string $view = 'filament.pages.data-backup';
    public ?array $data = [];

    public static function canAccess(): bool { return (bool) auth()->user()?->hasRole('Super Admin'); }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('term_id')->label('Results to download')->options(['' => 'All academic terms'] + AcademicTerm::query()->with('session')->orderByDesc('academic_session_id')->orderByDesc('sequence')->get()->mapWithKeys(fn ($term) => [$term->id => $term->session?->name.' — '.$term->name])->all())->native(false),
            Forms\Components\TextInput::make('backup_passphrase')->label('Credentials backup passphrase')->password()->revealable()->minLength(12)->helperText('Required to include and restore existing login passwords. Keep it separate from the ZIP and never email it.'),
            Forms\Components\FileUpload::make('results_zip')->label('Results ZIP to restore')->disk('local')->directory('backup-imports')->visibility('private')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(102400),
            Forms\Components\FileUpload::make('people_zip')->label('People ZIP to restore')->disk('local')->directory('backup-imports')->visibility('private')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(102400),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadResults')
                ->label('Download bulk results ZIP')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Download bulk results backup')
                ->modalDescription('Choose one academic term or all terms. The ZIP can be restored on this school or a new domain using Student IDs and subject/session identifiers.')
                ->form([
                    Forms\Components\Select::make('term_id')->label('Results to include')->options(['' => 'All academic terms'] + AcademicTerm::query()->with('session')->orderByDesc('academic_session_id')->orderByDesc('sequence')->get()->mapWithKeys(fn ($term) => [$term->id => $term->session?->name.' — '.$term->name])->all())->native(false)->required(),
                ])
                ->action(function (array $data) {
                    abort_unless(static::canAccess(), 403);
                    $term = filled($data['term_id'] ?? null) ? AcademicTerm::query()->find((int) $data['term_id']) : null;
                    $path = app(BackupArchiveService::class)->resultsZip($term);
                    return response()->download($path, 'emmaculate-results-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
                }),
            Action::make('restoreResults')
                ->label('Restore bulk results ZIP')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalHeading('Restore bulk results backup')
                ->modalDescription('Existing result rows are updated by Student ID, subject and academic term. Rows not in the ZIP are not deleted.')
                ->form([
                    Forms\Components\FileUpload::make('zip')->label('Results ZIP')->disk('local')->directory('backup-imports')->visibility('private')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(102400)->required(),
                ])
                ->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);
                    $stored = $data['zip'] ?? null;
                    $path = Storage::disk('local')->path(is_array($stored) ? (string) reset($stored) : (string) $stored);
                    try {
                        $result = app(BackupArchiveService::class)->importResults($path);
                        Notification::make()->success()->title('Results backup restored')->body($result['created'].' created, '.$result['updated'].' updated, '.count($result['errors']).' skipped.')->send();
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('Results backup could not be restored')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                    } finally {
                        @unlink($path);
                    }
                }),
            Action::make('downloadEncryptedPeople')
                ->label('Download encrypted people ZIP')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->modalHeading('Download encrypted people backup')
                ->modalDescription('This includes an encrypted credential file so users can keep their existing passwords on the new domain. Keep the passphrase separate from the ZIP and never email it.')
                ->form([
                    Forms\Components\TextInput::make('passphrase')->label('Backup passphrase')->password()->revealable()->required()->minLength(12)->helperText('Use the same passphrase during restore.'),
                ])
                ->action(function (array $data) {
                    abort_unless(static::canAccess(), 403);
                    $path = app(BackupArchiveService::class)->peopleZip((string) $data['passphrase']);
                    return response()->download($path, 'emmaculate-people-encrypted-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
                }),
            Action::make('restoreEncryptedPeople')
                ->label('Restore encrypted people ZIP')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalHeading('Restore encrypted people backup')
                ->modalDescription('Accounts, roles and biodata will be restored. Existing accounts matched by email will be updated. This does not delete accounts that are not in the ZIP.')
                ->form([
                    Forms\Components\FileUpload::make('zip')->label('Encrypted People ZIP')->disk('local')->directory('backup-imports')->visibility('private')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(102400)->required(),
                    Forms\Components\TextInput::make('passphrase')->label('Backup passphrase')->password()->revealable()->required()->minLength(12),
                ])
                ->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);
                    $stored = $data['zip'] ?? null;
                    $path = Storage::disk('local')->path(is_array($stored) ? (string) reset($stored) : (string) $stored);
                    try {
                        $result = app(BackupArchiveService::class)->importPeople($path, (string) $data['passphrase']);
                        Notification::make()->success()->title('People backup restored')->body($result['created'].' accounts/profiles created, '.$result['updated'].' updated, '.count($result['errors']).' skipped.')->send();
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('People backup could not be restored')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                    } finally {
                        @unlink($path);
                    }
                }),
        ];
    }

    public function downloadResults()
    {
        abort_unless(static::canAccess(), 403);
        $term = filled($this->data['term_id'] ?? null) ? AcademicTerm::query()->find((int) $this->data['term_id']) : null;
        $path = app(BackupArchiveService::class)->resultsZip($term);
        return response()->streamDownload(function () use ($path): void { readfile($path); @unlink($path); }, 'emmaculate-results-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip']);
    }

    public function downloadPeople()
    {
        abort_unless(static::canAccess(), 403); $passphrase = trim((string) ($this->data['backup_passphrase'] ?? ''));
        if (mb_strlen($passphrase) < 12) { Notification::make()->warning()->title('Enter a passphrase of at least 12 characters')->body('The passphrase encrypts the login credential hashes in the download.')->send(); return null; }
        $path = app(BackupArchiveService::class)->peopleZip($passphrase);
        return response()->streamDownload(function () use ($path): void { readfile($path); @unlink($path); }, 'emmaculate-people-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip']);
    }

    public function restore(): void
    {
        abort_unless(static::canAccess(), 403); $state = $this->form->getState(); $service = app(BackupArchiveService::class); $messages = [];
        foreach ([['results_zip', 'results'], ['people_zip', 'people']] as [$key, $label]) {
            $stored = $state[$key] ?? null; if (! $stored) continue;
            $path = Storage::disk('local')->path(is_array($stored) ? (string) reset($stored) : (string) $stored);
            try { $result = $label === 'results' ? $service->importResults($path) : $service->importPeople($path, (string) ($state['backup_passphrase'] ?? '')); $messages[] = ucfirst($label).': '.$result['created'].' created, '.$result['updated'].' updated, '.count($result['errors']).' skipped.'; }
            catch (ValidationException $exception) { $messages[] = ucfirst($label).': '.collect($exception->errors())->flatten()->implode(' '); }
            finally { @unlink($path); }
        }
        if ($messages === []) { Notification::make()->warning()->title('Choose a backup ZIP first')->send(); return; }
        $this->form->fill(); Notification::make()->success()->title('Backup restore finished')->body(implode(' ', $messages))->send();
    }
}
