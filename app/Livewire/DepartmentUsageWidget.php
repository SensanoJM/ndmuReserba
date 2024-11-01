<?php

namespace App\Livewire;

use App\Models\Department;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class DepartmentUsageWidget extends ChartWidget
{
    protected static ?string $heading = 'Department Usage';
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
        $departments = Department::with(['users.booking' => function ($query) {
            $this->applyTimeRangeFilter($query);
        }])->get();

        $labels = $departments->pluck('name')->toArray();
        $totalBookings = $departments->map(function ($department) {
            return $department->users->flatMap->booking->count();
        })->toArray();

        $approvedBookings = $departments->map(function ($department) {
            return $department->users->flatMap->booking
                ->where('status', 'approved')
                ->count();
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Bookings',
                    'data' => $totalBookings,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                ],
                [
                    'label' => 'Approved Bookings',
                    'data' => $approvedBookings,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
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
}