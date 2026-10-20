<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GraduatedStudentResource\Pages;
use App\Models\AuditLog;
use App\Models\Student;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** The archive of graduated students. They cannot sign in or check results; only administrators open their results here. */
class GraduatedStudentResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage students';
    protected static ?string $model = Student::class;
    protected static ?string $slug = 'graduated-students';
    protected static ?string $modelLabel = 'graduated student';
    protected static ?string $pluralModelLabel = 'Graduated students (archive)';
    protected static ?string $navigationLabel = 'Graduated students (archive)';
    protected static ?string $navigationGroup = 'Students & staff';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'graduated');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->with(['schoolClass', 'arm']))->columns([
            Tables\Columns\TextColumn::make('student_number')->label('Student ID')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('last_name')->label('Name')->formatStateUsing(fn ($state, Student $record) => $record->fullName())->searchable(['first_name', 'last_name', 'other_names']),
            Tables\Columns\TextColumn::make('schoolClass.name')->label('Last class'),
            Tables\Columns\TextColumn::make('graduated_at')->label('Graduated')->date()->sortable()->placeholder('—'),
        ])->defaultSort('graduated_at', 'desc')->actions([
            Tables\Actions\Action::make('results')->label('View results')->icon('heroicon-o-document-text')
                ->url(fn (Student $record) => route('staff.results.archive', ['q' => $record->student_number])),
            Tables\Actions\Action::make('restore')->label('Restore to active')->icon('heroicon-o-arrow-uturn-left')->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Use this only if a student was graduated by mistake. They will be able to sign in and check results again.')
                ->action(function (Student $record): void {
                    $record->forceFill(['status' => 'active', 'graduated_at' => null])->save();
                    AuditLog::record(auth()->user(), 'students.restored_from_archive', $record, []);
                    Notification::make()->success()->title('Student restored')->send();
                }),
        ])->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListGraduatedStudents::route('/')];
    }
}
