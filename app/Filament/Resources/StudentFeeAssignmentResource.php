<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentFeeAssignmentResource\Pages;
use App\Models\AcademicSession;
use App\Models\AcademicTerm;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StudentFeeAssignmentResource extends AuthorizedResource
{
    protected static ?string $requiredPermission = 'manage fees';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Fees owed';
    protected static ?string $navigationGroup = 'Fees & payments';
    protected static ?string $model = StudentFeeAssignment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->relationship('student', 'student_number', modifyQueryUsing: fn ($query) => $query->where('status', 'active'))->searchable()->preload()->getOptionLabelFromRecordUsing(fn (Student $record) => $record->fullName().' · '.$record->student_number)->required(),
            Forms\Components\Select::make('fee_type_id')->relationship('feeType', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('academic_session_id')->label('Academic session')->options(fn () => AcademicSession::query()->orderByDesc('starts_on')->pluck('name', 'id'))->searchable()->live(),
            Forms\Components\Select::make('academic_term_id')->label('Academic term')->options(fn (Get $get) => AcademicTerm::query()->when($get('academic_session_id'), fn ($query, $id) => $query->where('academic_session_id', $id))->orderBy('sequence')->get()->mapWithKeys(fn (AcademicTerm $term) => [$term->id => $term->name.' · '.$term->session?->name]))->searchable(),
            Forms\Components\TextInput::make('amount_due')->numeric()->minValue(0.01)->maxValue(100000000)->required()->prefix('₦'),
            Forms\Components\Select::make('currency')->options(['NGN' => 'NGN (Naira)'])->default('NGN')->required(),
            Forms\Components\DatePicker::make('due_date'),
            Forms\Components\Textarea::make('description')->rows(3)->maxLength(1000)->columnSpanFull(),
            Forms\Components\Placeholder::make('payment_state')->label('Payment state')->content(fn (?StudentFeeAssignment $record) => $record?->status ? ucfirst($record->status).' — state is changed only by verified payment settlement.' : 'New assignments are saved as unpaid.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['student', 'feeType']))->columns([
            Tables\Columns\TextColumn::make('student.student_number')->label('Student number')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('student.first_name')->label('Student')->formatStateUsing(fn ($state, StudentFeeAssignment $record) => $record->student?->fullName() ?? '—')->searchable(),
            Tables\Columns\TextColumn::make('feeType.name')->label('Fee')->searchable(),
            Tables\Columns\TextColumn::make('amount_due')->money('NGN')->sortable(),
            Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && $record->status !== 'paid' && ! $record->transactions()->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->transactions()->exists();
    }

    public static function canDeleteAny(): bool { return false; }
    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListStudentFeeAssignments::route('/'), 'create' => Pages\CreateStudentFeeAssignment::route('/create'), 'edit' => Pages\EditStudentFeeAssignment::route('/{record}/edit')]; }
}
