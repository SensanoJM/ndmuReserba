<?php

namespace App\Providers\Filament;

use App\Filament\Pages\BookingPage;
use App\Filament\Pages\TrackingPage;
use App\Livewire\UserCalendarWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Pages;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;


class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        //http://127.0.0.1:8000/user/login
        return $panel
            ->id('user')
            ->path('user')
            ->darkMode(false)
            ->topNavigation()
            ->brandLogo(asset('storage/images/GreenKey.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('storage/images/logo.png'))
            ->login(\App\Filament\Auth\UserLogin::class)
            ->registration(\App\Filament\Auth\UserRegister::class)
            ->pages([
                Pages\Dashboard::class,
                BookingPage::class,
                TrackingPage::class,
            ])
            ->plugin(
                FilamentFullCalendarPlugin::make()
                    ->timezone('UTC')  // Adjust to your timezone
                    ->locale('en')     // Adjust to your locale
            )
            ->widgets([
                UserCalendarWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}