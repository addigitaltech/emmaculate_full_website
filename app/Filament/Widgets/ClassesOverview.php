<?php

namespace App\Filament\Widgets;

use App\Models\SchoolClass;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ClassesOverview extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Classes and arms';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage academic structure') || $user->can('manage results')));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SchoolClass::query()->where('is_active', true)->with('arms')->withCount('students')->orderBy('name'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Class')->searchable(),
                Tables\Columns\TextColumn::make('arms.name')->label('Arms')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('students_count')->label('Students')->numeric(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
