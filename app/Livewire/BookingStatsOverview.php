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
                ->chart($bookingData->map(fn(TrendValue $value) => $value->aggregate)->toArray())
                ->color('success'),

            Stat::make('This Month', $bookingData->sum('aggregate'))
                ->description('Bookings this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->chart($bookingData->map(fn(TrendValue $value) => $value->aggregate)->toArray())
                ->color('success'),

            Stat::make('Total Users', User::count())
                ->description('Total users in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            // Additional stats to add to BookingStatsOverview
            Stat::make('Pending Approvals', Booking::where('status', 'prebooking')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approval Rate', function () {
                $total = Booking::count();
                $approved = Booking::where('status', 'approved')->count();
                return $total > 0 ? round(($approved / $total) * 100, 1) . '%' : '0%';
            })
                ->description('Overall approval rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Capacity Utilization', function () {
                $currentMonth = now()->month;
                $currentYear = now()->year;

                $bookings = Booking::where('status', 'approved')
                    ->whereMonth('booking_start', $currentMonth)
                    ->whereYear('booking_start', $currentYear)
                    ->with('facility')
                    ->get();

                if ($bookings->isEmpty()) {
                    return '0%';
                }

                $totalUtilization = 0;
                $facilityBookings = [];

                foreach ($bookings as $booking) {
                    if (!isset($facilityBookings[$booking->facility_id])) {
                        $facilityBookings[$booking->facility_id] = 0;
                    }

                    $duration = \Carbon\Carbon::parse($booking->booking_end)
                        ->diffInHours(\Carbon\Carbon::parse($booking->booking_start));

                    $facilityBookings[$booking->facility_id] += $duration;
                }

                $workingHours = 12 * 26; // 12 hours per day * ~26 working days
                $utilizationPercentage = collect($facilityBookings)
                    ->map(fn($hours) => min(100, ($hours / $workingHours) * 100))
                    ->average();

                return round($utilizationPercentage, 1) . '%';
            })
                ->description('Average facility usage this month')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success')
        ];
    }
}
