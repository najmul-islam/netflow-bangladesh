<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('user')
            ->path('/')
            ->login(\App\Filament\User\Pages\Auth\Login::class)
            ->colors([
                'primary' => [
                    50 => '#eff6ff',
                    100 => '#dbeafe', 
                    200 => '#bfdbfe',
                    300 => '#93c5fd',
                    400 => '#60a5fa',
                    500 => '#0B2E58',
                    600 => '#0B2E58',
                    700 => '#0B2E58',
                    800 => '#0B2E58',
                    900 => '#0B2E58',
                    950 => '#0B2E58',
                ],
                'secondary' => [
                    50 => '#fff7ed',
                    100 => '#ffedd5',
                    200 => '#fed7aa', 
                    300 => '#fdba74',
                    400 => '#fb923c',
                    500 => '#F76704',
                    600 => '#F76704',
                    700 => '#F76704',
                    800 => '#F76704',
                    900 => '#F76704',
                    950 => '#F76704',
                ],
            ])
            ->brandName('NetFlow Bangladesh')
            ->brandLogo(asset('images/logo.png'))
            ->favicon(asset('favicon.ico'))
            ->renderHook(
                'panels::body.end',
                fn (): string => '<style>
                    .fi-simple-layout { 
                        background: linear-gradient(135deg, #0b2e58 0%, #1e40af 25%, #f76704 75%, #ea580c 100%) !important; 
                        min-height: 100vh !important;
                    }
                    .fi-simple-main { 
                        background: rgba(255, 255, 255, 0.98) !important; 
                        backdrop-filter: blur(20px) !important;
                        border-radius: 24px !important;
                        box-shadow: 0 32px 64px -12px rgba(11, 46, 88, 0.3) !important;
                    }
                    .fi-btn-primary { 
                        background: linear-gradient(135deg, #0b2e58 0%, #0b2e58 100%) !important; 
                        border-radius: 12px !important;
                    }
                    .fi-btn-primary:hover { 
                        background: linear-gradient(135deg, #0b2e58 0%, #f76704 100%) !important; 
                        transform: translateY(-2px) !important;
                    }
                </style>'
            )
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\\Filament\\User\\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
            ->pages([
                \App\Filament\User\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/User/Widgets'), for: 'App\\Filament\\User\\Widgets')
            ->widgets([
                // Custom user widgets will be added here
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
            ])
            ->authGuard('web')
            ->viteTheme('resources/css/filament/user/theme.css')
            ->spa()
            ->darkMode(false);
    }
}