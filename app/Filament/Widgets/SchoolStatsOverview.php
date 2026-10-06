<?php

namespace App\Filament\Widgets;

use App\Models\Arm;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->hasRole('Super Admin') || $user->can('manage students') || $user->can('manage results')));
    }

    protected function getStats(): array
    {
        $stats = [
            Stat::make('Students', Student::query()->where('status', 'active')->count())->description('Active students')->descriptionIcon('heroicon-m-academic-cap')->color('primary'),
            Stat::make('Teachers', Teacher::query()->where('status', 'active')->count())->description('Active teachers')->descriptionIcon('heroicon-m-user-group')->color('info'),
            Stat::make('Classes', SchoolClass::query()->where('is_active', true)->count())->description('Active classes')->descriptionIcon('heroicon-m-building-library')->color('warning'),
            Stat::make('Arms', Arm::query()->count())->description('Class arms')->descriptionIcon('heroicon-m-squares-2x2')->color('gray'),
            Stat::make('Subjects', Subject::query()->where('status', 'active')->count())->description('Active subjects')->descriptionIcon('heroicon-m-book-open')->color('success'),
        ];
        if (auth()->user()?->can('publish results')) {
            $stats[] = Stat::make('Awaiting publication', Result::query()->whereIn('status', ['pending', 'draft'])->count())->description('Pending result lines')->descriptionIcon('heroicon-m-clock')->color('danger');
        }

        return $stats;
    }
}
