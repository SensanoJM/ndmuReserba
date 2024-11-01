<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Livewire\ReservationForm;
use Filament\Forms\Components\Livewire;
use App\Models\Reservation;
use App\Observers\ReservationObserver;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Colors\Color;
use App\Livewire\CalendarWidget;
use App\Models\Booking;
use App\Repositories\FacilityRepository;
use App\Services\ReservationService;
use App\Services\BookingService;
use App\Models\Signatory;
use App\Observers\BookingObserver;
use App\Observers\SignatoryApprovalObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FacilityRepository::class, function ($app) {
            return new FacilityRepository();
        });

        $this->app->bind(ReservationService::class, function ($app) {
            return new ReservationService();
        });

        $this->app->bind(BookingService::class, function ($app) {
            return new BookingService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Booking::observe(BookingObserver::class);
        Signatory::observe(SignatoryApprovalObserver::class);
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
