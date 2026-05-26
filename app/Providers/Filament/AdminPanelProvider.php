<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomLogin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->login(CustomLogin::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->brandLogo(view('filament.components.brand-logo'))
            ->brandLogoHeight('44px')
            ->favicon(asset('favicon.ico'))
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)
            ->globalSearchKeyBindings(['ctrl+k', 'ctrl+/'])
            ->globalSearchDebounce('300ms')
            ->unsavedChangesAlerts()
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Green,
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'info' => Color::Sky,
                'gray' => Color::Zinc,
            ])
            ->navigationGroups([
                NavigationGroup::make('المبيعات'),
                NavigationGroup::make('المشتريات'),
                NavigationGroup::make('المخزون'),
                NavigationGroup::make('العملاء والموردين'),
                NavigationGroup::make('الخزينة والمالية'),
                NavigationGroup::make('المحاسبة'),
                NavigationGroup::make('التقارير')
                    ->collapsed(),
                NavigationGroup::make('الشركات والأصناف')
                    ->collapsed(),
                NavigationGroup::make('العمليات الداخلية')
                    ->collapsed(),
                NavigationGroup::make('إدارة البيانات')
                    ->collapsed(),
                NavigationGroup::make('الإعدادات')
                    ->collapsed(),
                NavigationGroup::make('الإدارة')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // custom widgets auto-discovered above — no Filament defaults
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
