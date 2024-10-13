<?php

namespace App\Livewire;

use App\Models\Facility;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class FacilityUsageWidget extends ChartWidget
{
    protected static ?string $heading = 'Facility Usage';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $facilities = Facility::withCount('bookings')
            ->orderBy('bookings_count', 'desc')
            ->get();

        Log::info('Facilities retrieved: ', $facilities->toArray());

        $labels = $facilities->pluck('facility_name')->toArray();
        $bookingCounts = $facilities->pluck('bookings_count')->toArray();

        $totalBookings = array_sum($bookingCounts);
        $usagePercentages = array_map(function ($count) use ($totalBookings) {
            return $totalBookings > 0 ? round(($count / $totalBookings) * 100, 2) : 0;
        }, $bookingCounts);

        Log::info('Chart data:', [
            'labels' => $labels,
            'bookingCounts' => $bookingCounts,
            'usagePercentages' => $usagePercentages,
        ]);

        return [
            'datasets' => [
                [
                    'label' => 'Booking Count',
                    'data' => $bookingCounts,
                    'backgroundColor' => $this->getColors(count($facilities)),
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Usage Percentage',
                    'data' => $usagePercentages,
                    'type' => 'line',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'yAxisID' => 'percentage',
                ],
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
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Booking Count',
                    ],
                ],
                'percentage' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Usage Percentage',
                    ],
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }",
                    ],
                ],
            ],
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Facility Usage Overview',
                ],
            ],
        ];
    }

    protected function getColors(int $count): array
    {
        $colors = [
            'rgba(75, 192, 192, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(255, 99, 132, 0.6)',
        ];

        return array_pad(array_slice($colors, 0, $count), $count, $colors[0]);
    }
}
