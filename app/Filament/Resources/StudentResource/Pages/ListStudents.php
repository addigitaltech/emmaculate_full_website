<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Domain\Results\Services\StudentCsvImporter;
use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importStudents')
                ->label('Import from CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => StudentResource::canCreate())
                ->modalHeading('Import students from a CSV file')
                ->modalDescription('Columns: student_number, first_name, last_name, other_names, gender, date_of_birth (YYYY-MM-DD or DD/MM/YYYY), class, arm, status. Classes and arms must already exist. Existing students are matched on student_number and updated.')
                ->modalSubmitActionLabel('Import')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV file')
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->maxSize(2048)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $relative = (string) $data['file'];
                    $path = Storage::disk('local')->path($relative);
                    try {
                        $result = app(StudentCsvImporter::class)->import($path);
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('Import failed')->body(collect($exception->errors())->flatten()->implode(' '))->persistent()->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($relative);
                    }
                    $body = $result['created'].' created, '.$result['updated'].' updated.';
                    if ($result['errors'] !== []) {
                        $body .= ' '.count($result['errors']).' row(s) skipped: '.implode(' | ', array_slice($result['errors'], 0, 8));
                    }
                    $notice = Notification::make()->title('Student import finished')->body($body);
                    ($result['errors'] === [] ? $notice->success() : $notice->warning())->persistent()->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
