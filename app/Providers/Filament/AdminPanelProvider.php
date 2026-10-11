<?php

namespace App\Providers\Filament;

use App\Models\SchoolSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\View\PanelsRenderHook;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => SchoolSettings::current()->school_name.' admin')
            ->brandLogo(fn () => ($path = SchoolSettings::current()->logo_path) ? asset('storage/'.$path) : null)
            ->brandLogoHeight('2.6rem')
            ->favicon(fn () => ($path = SchoolSettings::current()->favicon_path) ? asset('storage/'.$path) : null)
            ->colors([
                'primary' => Color::Red,
                'info' => Color::Blue,
                'warning' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('Website'),
                NavigationGroup::make('School profile'),
                NavigationGroup::make('Homepage & menus')->collapsed(),
                NavigationGroup::make('Admissions & messages'),
                NavigationGroup::make('People'),
                NavigationGroup::make('Academics setup')->collapsed(),
                NavigationGroup::make('Results'),
                NavigationGroup::make('Fees & payments')->collapsed(),
                NavigationGroup::make('System')->collapsed(),
            ])
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.back-to-site'))
            ->navigationItems([
                NavigationItem::make('Manage results')
                    ->url(fn () => route('staff.results'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->group('Results')
                    ->sort(1)
                    ->visible(fn () => (bool) auth()->user()?->can('manage results')),
                NavigationItem::make('Reports archive')
                    ->url(fn () => route('staff.results.archive'))
                    ->icon('heroicon-o-archive-box')
                    ->group('Results')
                    ->sort(2)
                    ->visible(fn () => (bool) auth()->user()?->can('manage results')),
                NavigationItem::make('View website')
                    ->url(fn () => route('home'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-globe-alt')
                    ->sort(99),
            ])
            ->userMenuItems([
                MenuItem::make()->label('Staff portal')->url(fn () => route('portal.dashboard'))->icon('heroicon-o-home'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
