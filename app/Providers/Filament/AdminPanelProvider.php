<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Widgets\LatestQuestionIntegrityAuditOverview;
use App\Filament\Widgets\PlatformOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Enums\ThemeMode;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
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
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->login()
            ->brandName(config('app.name'))
            ->theme(asset('css/filament/admin/theme.css?v=20260416-admin-questions-search-layout'))
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): \Illuminate\Contracts\View\View => view('filament.partials.return-home-button'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): \Illuminate\Contracts\View\View => view('filament.partials.return-home-login-link'),
            )
            ->colors([
                'primary' => Color::Sky,
                'gray' => Color::Slate,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->maxContentWidth(Width::Full)
            ->navigationGroups([
                'Zawartość',
                'Operacje',
                'Dostęp',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PlatformOverview::class,
                LatestQuestionIntegrityAuditOverview::class,
                AccountWidget::class,
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
