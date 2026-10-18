<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Arm;
use App\Models\MediaAsset;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use App\Domain\Results\Services\PinSlipService;
use App\Models\SchoolSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StudentResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage students';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'People';
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('student_number')->label('Student ID')->disabled()->dehydrated(false)->placeholder('Assigned automatically when you save')
                ->helperText('Created automatically (school code / session / running number) and never typed in. It cannot be changed.'),
            Forms\Components\TextInput::make('first_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('last_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('other_names')->maxLength(150),
            Forms\Components\Select::make('gender')->options(['Female' => 'Female', 'Male' => 'Male'])->required(),
            Forms\Components\DatePicker::make('date_of_birth')->maxDate(now())->required(),
            Forms\Components\Select::make('school_class_id')->label('Class')->relationship('schoolClass', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))->searchable()->preload()->required()->live()->afterStateUpdated(fn (Set $set) => $set('arm_id', null)),
            Forms\Components\Select::make('arm_id')->label('Arm')->options(fn (Get $get) => Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->orderBy('name')->pluck('name', 'id'))->searchable()->placeholder('No arm')
                ->required(fn (Get $get): bool => (bool) $get('school_class_id') && Arm::query()->whereHas('classes', fn ($query) => $query->whereKey($get('school_class_id')))->exists()),
            Forms\Components\Select::make('photo_id')->label('Passport photograph')->relationship('photo', 'original_name', modifyQueryUsing: fn ($query) => $query->where('mime_type', 'like', 'image/%'))->searchable()->preload()->placeholder('No passport photograph')
                ->helperText('Choose an existing photo, or use the + button to upload a passport photograph straight from this page.')
                ->createOptionModalHeading('Upload passport photograph')
                ->createOptionForm([
                    Forms\Components\FileUpload::make('path')->label('Passport photograph (JPEG, PNG or WebP, up to 2 MB)')->disk('public')->directory('student-passports')->visibility('public')->storeFileNamesIn('original_name')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(2048)->required(),
                ])
                ->createOptionUsing(function (array $data): int {
                    $path = (string) ($data['path'] ?? '');
                    if (! str_starts_with($path, 'student-passports/') || str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
                        throw ValidationException::withMessages(['path' => 'Choose a valid passport photograph to upload.']);
                    }
                    $mime = Storage::disk('public')->mimeType($path) ?: '';
                    if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || Storage::disk('public')->size($path) > 2 * 1024 * 1024) {
                        Storage::disk('public')->delete($path);
                        throw ValidationException::withMessages(['path' => 'The passport must be a JPEG, PNG or WebP image of 2 MB or less.']);
                    }

                    return MediaAsset::query()->create([
                        'uploaded_by' => auth()->id(),
                        'original_name' => $data['original_name'] ?? basename($path),
                        'path' => $path,
                        'disk' => 'public',
                        'mime_type' => $mime,
                        'size_bytes' => Storage::disk('public')->size($path),
                        'alt_text' => 'Student passport photograph',
                    ])->getKey();
                }),
            Forms\Components\DatePicker::make('admission_date')->label('Date admitted')->default(now())->required(),
            Forms\Components\Select::make('parents')->label('Parent / guardian (optional)')->relationship('parents', 'full_name')->multiple()->searchable()->preload()
                ->getOptionLabelFromRecordUsing(fn (ParentProfile $record) => $record->full_name.($record->email ? ' · '.$record->email : ''))
                ->helperText('Only parents can be linked here, and linking is optional. You can also link or change parents later when editing this student.')->columnSpanFull(),
            Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated', 'transferred' => 'Transferred'])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['schoolClass', 'arm', 'user']))->columns([
            Tables\Columns\TextColumn::make('student_number')->label('Student ID')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('first_name')->searchable(),
            Tables\Columns\TextColumn::make('last_name')->searchable(),
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
            Tables\Columns\TextColumn::make('arm.name')->label('Arm'),
            Tables\Columns\TextColumn::make('user.email')->label('Portal account')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('admission_date')->date()->sortable()->toggleable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('school_class_id')->label('Class')->relationship('schoolClass', 'name'),
            Tables\Filters\SelectFilter::make('arm_id')->label('Arm')->relationship('arm', 'name'),
            Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated', 'transferred' => 'Transferred'])->default('active'),
        ])->actions([
            Tables\Actions\Action::make('enterResult')->label('Enter result')->icon('heroicon-o-pencil-square')->color('success')
                ->url(fn (Student $record) => route('staff.results.student', $record))
                ->visible(fn () => (bool) auth()->user()?->can('manage results')),
            Tables\Actions\Action::make('printResult')->label('Print result')->icon('heroicon-o-printer')->color('info')
                ->url(function (Student $record) {
                    $term = \App\Models\AcademicTerm::query()->where('is_current', true)->first();

                    return $term ? route('reports.show', ['student' => $record->id, 'term' => $term->id]) : null;
                }, shouldOpenInNewTab: true)
                ->visible(fn () => (bool) auth()->user()?->can('manage results')),
            Tables\Actions\Action::make('issuePin')->label('PIN slip')->icon('heroicon-o-key')->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Issue a new PIN')
                ->modalDescription('This creates a new 6-digit PIN for the student (any earlier PIN stops working) and downloads a printable slip. The PIN is shown only on that slip.')
                ->action(fn (Student $record) => static::pinSlipsResponse(collect([$record])))
                ->visible(fn () => (bool) auth()->user()?->can('manage students')),
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('issuePins')->label('Issue PIN slips')->icon('heroicon-o-key')->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Issue PINs for the selected students')
                ->modalDescription('Creates a new 6-digit PIN for every selected active student (earlier PINs stop working) and downloads printable slips. PINs are shown only on the slips.')
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records) => static::pinSlipsResponse($records))
                ->visible(fn () => (bool) auth()->user()?->can('manage students')),
        ]);
    }

    /** Issue PINs for the given students and return the printable A4 slips. PINs are never stored in readable form. */
    protected static function pinSlipsResponse(Collection $students)
    {
        abort_unless(auth()->user()?->can('manage students'), 403);
        $students = $students->filter(fn (Student $student) => $student->status === 'active')->values();
        if ($students->isEmpty()) {
            Notification::make()->title('Select at least one active student')->warning()->send();

            return null;
        }

        return app(PinSlipService::class)->download($students);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListStudents::route('/'), 'create' => Pages\CreateStudent::route('/create'), 'edit' => Pages\EditStudent::route('/{record}/edit')]; }
}
