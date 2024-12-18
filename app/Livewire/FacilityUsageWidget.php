<?php

namespace App\Livewire;

use App\Models\Facility;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class FacilityUsageWidget extends ChartWidget
{
    protected static ?string $heading = 'Facility Usage';
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = 'this_month';

    protected function getFilters(): ?array
    {
        return [
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $facilities = Facility::withCount(['bookings' => function ($query) {
            $this->applyTimeRangeFilter($query);
        }])->get();

        $labels = $facilities->pluck('facility_name')->toArray();
        $bookingCounts = $facilities->pluck('bookings_count')->toArray();

        $totalBookings = array_sum($bookingCounts);
        $usagePercentages = array_map(function ($count) use ($totalBookings) {
            return $totalBookings > 0 ? round(($count / $totalBookings) * 100, 2) : 0;
        }, $bookingCounts);

        return [
            'datasets' => [
                [
                    'label' => 'Booking Count',
                    'data' => $bookingCounts,
                    'backgroundColor' => $this->getBackgroundColors($facilities->count()),
                ],
                [
                    'label' => 'Usage Percentage',
                    'data' => $usagePercentages,
                    'type' => 'line',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'fill' => false,
                ]
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Bookings'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top'
                ]
            ]
        ];
    }

    protected function applyTimeRangeFilter($query): void
    {
        $now = Carbon::now();
        
        match ($this->filter) {
            'this_month' => $query->whereMonth('booking_start', $now->month)
                                ->whereYear('booking_start', $now->year),
            'last_month' => $query->whereMonth('booking_start', $now->subMonth()->month)
                                ->whereYear('booking_start', $now->year),
            'this_year' => $query->whereYear('booking_start', $now->year),
            default => $query
        };
    }

    protected function getBackgroundColors(int $count): array
    {
        $colors = [
            'rgba(75, 192, 192, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(255, 99, 132, 0.6)',
        ];

        return array_slice($colors, 0, $count);
    }
}