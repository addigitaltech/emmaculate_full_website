<?php

namespace App\Filament\Pages;

use App\Domain\Website\PictureLibrary;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Super Admin: check that every picture the website uses exists, download them all as one ZIP, or load a ZIP back in. */
class PicturesBackup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Pictures check & backup';
    protected static ?int $navigationSort = 8;
    protected static ?string $title = 'Pictures: check, download and restore';
    protected static ?string $slug = 'pictures-backup';
    protected static string $view = 'filament.pages.pictures-backup';

    public ?array $data = [];
    public array $missing = [];
    public ?int $checkedTotal = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('Super Admin');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('zip')->label('Pictures ZIP to load into this website')
                ->disk('local')->directory('pictures-import')->visibility('private')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(51200),
        ])->statePath('data');
    }

    public function check(): void
    {
        abort_unless(static::canAccess(), 403);
        $references = app(PictureLibrary::class)->references();
        $this->checkedTotal = $references->count();
        $this->missing = $references->where('exists', false)->map(fn ($row) => $row['path'].'  (used by '.$row['table'].')')->values()->all();
        Notification::make()->title($this->missing === [] ? 'All '.$this->checkedTotal.' pictures are in place' : count($this->missing).' of '.$this->checkedTotal.' pictures are missing')
            ->{$this->missing === [] ? 'success' : 'warning'}()->send();
    }

    public function download()
    {
        abort_unless(static::canAccess(), 403);
        try {
            $tmp = app(PictureLibrary::class)->zip();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Could not make the ZIP')->body(collect($exception->errors())->flatten()->implode(' '))->send();

            return null;
        }

        return response()->streamDownload(function () use ($tmp): void {
            readfile($tmp);
            @unlink($tmp);
        }, 'website-pictures-'.now()->format('Ymd-His').'.zip', ['Content-Type' => 'application/zip']);
    }

    public function restore(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->form->getState();
        $stored = $state['zip'] ?? null;
        if (! $stored) {
            Notification::make()->warning()->title('Choose a ZIP file first')->send();

            return;
        }
        $path = Storage::disk('local')->path(is_array($stored) ? (string) reset($stored) : (string) $stored);
        try {
            $result = app(PictureLibrary::class)->import($path);
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Could not read the ZIP')->body(collect($exception->errors())->flatten()->implode(' '))->send();

            return;
        } finally {
            @unlink($path);
        }
        $this->form->fill();
        Notification::make()->success()->title($result['added'].' picture(s) added')
            ->body($result['skipped'].' already existed and were kept.'.($result['rejected'] ? ' '.count($result['rejected']).' file(s) were refused (not a safe picture).' : ''))->send();
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('check')->label('Check pictures')->icon('heroicon-o-magnifying-glass')->action('check'), Action::make('download')->label('Download all pictures (ZIP)')->icon('heroicon-o-arrow-down-tray')->color('gray')->action('download')];
    }
}
