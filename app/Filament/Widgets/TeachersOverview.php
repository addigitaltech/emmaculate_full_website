<?php

namespace App\Filament\Widgets;

use App\Models\Teacher;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TeachersOverview extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Teachers';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage teachers')));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Teacher::query()->where('status', 'active')->withCount('assignments')->orderBy('last_name'))
            ->columns([
                Tables\Columns\TextColumn::make('last_name')->label('Teacher name')->formatStateUsing(fn ($state, Teacher $record) => strtoupper($record->last_name).', '.trim($record->first_name.' '.$record->other_names))->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('staff_number')->label('Staff no.')->placeholder('—'),
                Tables\Columns\TextColumn::make('assignments_count')->label('Subjects assigned')->numeric(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
