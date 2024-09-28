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
use App\Filament\Pages\SignatoryPage;
use Filament\Notifications\Livewire\DatabaseNotifications;

class SignatoryPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('signatory')
            ->path('signatory')
            ->login(\App\Filament\Auth\SignatoryLogin::class)
            ->colors([
                'primary' => Color::Blue,
            ])
            // ->discoverResources(in: app_path('Filament/Signatory/Resources'), for: 'App\\Filament\\Signatory\\Resources')
            ->discoverPages(in: app_path('Filament/Signatory/Pages'), for: 'App\\Filament\\Signatory\\Pages')
            ->pages([
                SignatoryPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Signatory/Widgets'), for: 'App\\Filament\\Signatory\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->topbar(DatabaseNotifications::class);
    }
}
