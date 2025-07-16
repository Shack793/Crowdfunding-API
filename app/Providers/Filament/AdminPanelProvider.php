<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
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
            ->brandName('WGCrowdfunding')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => [
                    '50' => '#fffbeb',
                    '100' => '#fef3c7',
                    '200' => '#fde68a',
                    '300' => '#fcd34d',
                    '400' => '#fbbf24',
                    '500' => '#f59e0b',
                    '600' => '#d97706',
                    '700' => '#b45309',
                    '800' => '#92400e',
                    '900' => '#78350f',
                ],
                'danger' => [
                    '50' => '#fef2f2',
                    '100' => '#fee2e2',
                    '200' => '#fecaca',
                    '300' => '#fca5a5',
                    '400' => '#f87171',
                    '500' => '#ef4444',
                    '600' => '#dc2626',
                    '700' => '#b91c1c',
                    '800' => '#991b1b',
                    '900' => '#7f1d1d',
                ],
                'success' => [
                    '50' => '#ecfdf5',
                    '100' => '#d1fae5',
                    '200' => '#a7f3d0',
                    '300' => '#6ee7b7',
                    '400' => '#34d399',
                    '500' => '#10b981',
                    '600' => '#059669',
                    '700' => '#047857',
                    '800' => '#065f46',
                    '900' => '#064e3b',
                ],
                'warning' => [
                    '50' => '#fffbeb',
                    '100' => '#fef3c7',
                    '200' => '#fde68a',
                    '300' => '#fcd34d',
                    '400' => '#fbbf24',
                    '500' => '#f59e0b',
                    '600' => '#d97706',
                    '700' => '#b45309',
                    '800' => '#92400e',
                    '900' => '#78350f',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            //->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->widgets([
                // Widgets temporarily disabled for maintenance
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->darkMode(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                'Content',
                'Administration',
                'Settings',
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Zinc,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Blue,
            ])
            ->font('Inter')
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
            ])
            ->pages([
                Pages\Dashboard::class,
            ]);
    }
}
