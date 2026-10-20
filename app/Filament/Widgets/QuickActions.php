<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\PortalSettings;
use App\Filament\Pages\SchoolProfile;
use App\Filament\Pages\StudentPinSlips;
use App\Filament\Resources\NewsPostResource;
use App\Filament\Resources\StudentFeeAssignmentResource;
use App\Filament\Resources\StudentResource;
use Filament\Widgets\Widget;

/** "Start here": big buttons for the jobs people do most, so nobody has to hunt through the menu. */
class QuickActions extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';
    protected static bool $isLazy = false;
    protected static ?int $sort = -10;
    protected int|string|array $columnSpan = 'full';

    /** @return array<int, array{label: string, hint: string, url: string, icon: string}> */
    public function getActions(): array
    {
        $user = auth()->user();
        $can = fn (string $permission): bool => (bool) ($user && ($user->hasRole('Super Admin') || $user->can($permission)));
        $actions = [];

        if ($can('manage students')) {
            $actions[] = ['label' => 'Add a student', 'hint' => 'Student ID is made for you', 'url' => StudentResource::getUrl('create'), 'icon' => 'heroicon-o-user-plus'];
            $actions[] = ['label' => 'Print PIN slips', 'hint' => 'One class or the whole school', 'url' => StudentPinSlips::getUrl(), 'icon' => 'heroicon-o-key'];
        }
        if ($can('manage results')) {
            $actions[] = ['label' => 'Results', 'hint' => 'Enter, publish and print', 'url' => route('staff.results'), 'icon' => 'heroicon-o-clipboard-document-list'];
            $actions[] = ['label' => 'Reports archive', 'hint' => 'Look up any past report', 'url' => route('staff.results.archive'), 'icon' => 'heroicon-o-archive-box'];
        }
        if ($can('manage payments')) {
            $actions[] = ['label' => 'School fees', 'hint' => 'Who owes and who has paid', 'url' => StudentFeeAssignmentResource::getUrl(), 'icon' => 'heroicon-o-banknotes'];
        }
        if ($can('manage website content')) {
            $actions[] = ['label' => 'Post news', 'hint' => 'Publish on the website', 'url' => NewsPostResource::getUrl('create'), 'icon' => 'heroicon-o-newspaper'];
        }
        if ($user && $user->hasRole('Super Admin')) {
            $actions[] = ['label' => 'School profile', 'hint' => 'Name, logo, contacts, Student ID prefix', 'url' => SchoolProfile::getUrl(), 'icon' => 'heroicon-o-building-office-2'];
            $actions[] = ['label' => 'Portal switches', 'hint' => 'Turn portals and PIN on or off', 'url' => PortalSettings::getUrl(), 'icon' => 'heroicon-o-adjustments-horizontal'];
        }

        return $actions;
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
