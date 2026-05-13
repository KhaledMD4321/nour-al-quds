<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomLogin;
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
            ->brandLogo(view('filament.components.brand-logo'))
            ->brandLogoHeight('44px')
            ->favicon(asset('favicon.ico'))
            ->darkMode(false)
            ->globalSearchKeyBindings(['ctrl+k', 'ctrl+/'])
            ->globalSearchDebounce('300ms')
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Green,
                'danger'  => Color::Red,
                'warning' => Color::Amber,
                'info'    => Color::Sky,
                'gray'    => Color::Zinc,
            ])
            ->navigationGroups([
                NavigationGroup::make('المبيعات')
                    ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make('المشتريات')
                    ->icon('heroicon-o-truck'),
                NavigationGroup::make('المخزون')
                    ->icon('heroicon-o-cube'),
                NavigationGroup::make('العملاء والموردين')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('الخزينة والمالية')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make('المحاسبة')
                    ->icon('heroicon-o-calculator'),
                NavigationGroup::make('التقارير')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(),
                NavigationGroup::make('الشركات والأصناف')
                    ->icon('heroicon-o-tag')
                    ->collapsed(),
                NavigationGroup::make('العمليات الداخلية')
                    ->icon('heroicon-o-arrows-right-left')
                    ->collapsed(),
                NavigationGroup::make('إدارة البيانات')
                    ->icon('heroicon-o-circle-stack')
                    ->collapsed(),
                NavigationGroup::make('الإعدادات')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make('الإدارة')
                    ->icon('heroicon-o-shield-check')
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
