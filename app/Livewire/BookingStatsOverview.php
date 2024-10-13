<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class BookingStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $bookingData = Trend::model(Booking::class)
            ->between(
                start: now()->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perDay()
            ->count();

        return [
            Stat::make('Total Bookings', Booking::count())
                ->description('Total bookings in the system')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($bookingData->map(fn (TrendValue $value) => $value->aggregate)->toArray())
                ->color('primary'),

            Stat::make('This Month', $bookingData->sum('aggregate'))
                ->description('Bookings this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->chart($bookingData->map(fn (TrendValue $value) => $value->aggregate)->toArray())
                ->color('primary'),

            Stat::make('Total Users', User::count())
                ->description('Total users in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
