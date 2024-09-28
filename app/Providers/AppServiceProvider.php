<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Livewire\ReservationForm;
use Filament\Forms\Components\Livewire;
use App\Models\Reservation;
use App\Observers\ReservationObserver;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Colors\Color;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Reservation::observe(ReservationObserver::class);
        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Emerald,
            'success' => Color::Green,
            'warning' => Color::Amber,
        ]);
        
    }
}
