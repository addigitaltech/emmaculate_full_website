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
                ->modalDescription('Columns: first_name, last_name, other_names, gender, date_of_birth (YYYY-MM-DD or DD/MM/YYYY), class, arm, status, and optionally student_id. New students get their Student ID automatically, so leave student_id blank; put an existing Student ID there only to update that student. Classes and arms must already exist. Existing students are matched on student_number and updated.')
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
            Actions\Action::make('downloadTemplate')
                ->label('Download upload sheet')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => StudentResource::canCreate())
                ->modalHeading('Make an upload sheet for the whole school')
                ->modalDescription('Creates a spreadsheet with empty lines for every class and arm, class and arm already filled in. Open it in Excel or Google Sheets, type each student\'s names, save as CSV and use Import from CSV. Empty lines are ignored and Student IDs are created for you.')
                ->modalSubmitActionLabel('Download')
                ->form([
                    \Filament\Forms\Components\Select::make('classes')->label('Classes to include (leave empty for all)')->multiple()->searchable()
                        ->options(fn () => \App\Models\SchoolClass::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')),
                    \Filament\Forms\Components\TextInput::make('lines_per_arm')->label('Empty lines per arm')->numeric()->minValue(1)->maxValue(200)->default(40)->required()
                        ->helperText('Roughly the largest number of students you expect in one arm. Up to 2,000 lines per file.'),
                ])
                ->action(function (array $data) {
                    try {
                        $csv = app(\App\Domain\Results\Services\StudentTemplateGenerator::class)->csv($data['classes'] ?: null, (int) $data['lines_per_arm']);
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('Could not make the sheet')->body(collect($exception->errors())->flatten()->implode(' '))->persistent()->send();

                        return null;
                    }

                    return response()->streamDownload(function () use ($csv): void {
                        echo $csv;
                    }, 'student-upload-sheet-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
            Actions\Action::make('printPinSlips')->label('Print PIN slips')->icon('heroicon-o-printer')->color('gray')
                ->url(fn () => \App\Filament\Pages\StudentPinSlips::getUrl())
                ->visible(fn () => \App\Filament\Pages\StudentPinSlips::canAccess()),
            Actions\Action::make('assignStudentIds')->label('Assign Student IDs to older records')->icon('heroicon-o-identification')->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Give older students a Student ID')
                ->modalDescription(fn () => \App\Domain\Identity\StudentIdGenerator::legacyCount().' student(s) still have an older number. They will each get a new Student ID in the school format (for example EAC/2627/0001), oldest records first. Anything printed with the old number, such as earlier PIN slips, must be reprinted.')
                ->visible(fn () => StudentResource::canCreate() && \App\Domain\Identity\StudentIdGenerator::legacyCount() > 0)
                ->action(function (): void {
                    $count = \App\Domain\Identity\StudentIdGenerator::convertLegacy();
                    Notification::make()->success()->title($count.' student(s) now have a Student ID')->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
