<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use App\Models\Booking;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Support\Carbon;

class CalendarWidget extends FullCalendarWidget
{

    public Model|string|null $model = Booking::class;

    /**
     * Fetches the events for the calendar.
     *
     * @param array $fetchInfo
     * @return array
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $cacheKey = $this->generateCacheKey($fetchInfo);
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($fetchInfo) {
            Log::info('Fetching events', $fetchInfo);
            
            $bookings = $this->fetchApprovedBookings($fetchInfo);
            $events = $this->mapBookingsToEvents($bookings);
            
            Log::info('Events data being returned', ['events' => $events]);
            
            return $events;
        });
    }

    private function generateCacheKey(array $fetchInfo): string
    {
        return "calendar_events_{$fetchInfo['start']}_{$fetchInfo['end']}";
    }

    private function fetchApprovedBookings(array $fetchInfo): \Illuminate\Database\Eloquent\Collection
    {
        $bookings = Booking::query()
            ->with('user', 'facility')
            ->where('status', 'approved')
            ->whereBetween('booking_date', [$fetchInfo['start'], $fetchInfo['end']])
            ->get();

        Log::info('Fetched bookings', ['count' => $bookings->count()]);

        return $bookings;
    }

    private function mapBookingsToEvents(\Illuminate\Database\Eloquent\Collection $bookings): array
    {
        return $bookings->map(function (Booking $booking) {
            Log::info('Processing booking', $this->getBookingLogData($booking));

            $startDateTime = $this->combineDateTime($booking->booking_date, $booking->start_time);
            $endDateTime = $this->combineDateTime($booking->booking_date, $booking->end_time);

            return $this->createEventData($booking, $startDateTime, $endDateTime);
        })->toArray();
    }

    private function getBookingLogData(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'purpose' => $booking->purpose,
            'date' => $booking->booking_date,
            'start' => $booking->start_time,
            'end' => $booking->end_time,
        ];
    }

    private function combineDateTime($date, $time): Carbon
    {
        $dateTime = $date instanceof Carbon ? $date : Carbon::parse($date);
        $time = $time instanceof Carbon ? $time : Carbon::parse($time);

        return $dateTime->setTime($time->hour, $time->minute, $time->second);
    }

    private function createEventData(Booking $booking, Carbon $start, Carbon $end): array
    {
        return EventData::make()
            ->id($booking->id)
            ->title($booking->purpose)
            ->start($start->toDateTimeString())
            ->end($end->toDateTimeString())
            ->allDay(false)
            ->backgroundColor($this->getEventColor($booking->user->role))
            ->extendedProps([
                'user' => $booking->user->name,
                'facility' => $booking->facility->facility_name,
            ])
            ->toArray();
    }

    public function getEventColor(string $userRole): string
    {
        return match ($userRole) {
            'organization' => '#4CAF50', // Green
            'faculty' => '#2196F3', // Blue
            'admin' => '#FF5722', // Deep Orange
            default => '#9C27B0', // Purple
        };
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->form(fn(Form $form) => $form
                    ->schema([
                        DatePicker::make('booking_date')
                            ->label('Booking Date')
                            ->required(),
                        TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required(),
                        TimePicker::make('end_time')
                            ->label('End Time')
                            ->required(),
                    ])
            )
            ->mutateRecordDataUsing(function (array $data): array {
                $data['start_time'] = date('H:i:s', strtotime($data['start_time']));
                $data['end_time'] = date('H:i:s', strtotime($data['end_time']));
                return $data;
            })
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Booking updated')
                    ->body('The booking has been updated successfully.')
            );
    }

    protected function headerActions(): array
    {
        return [];
    }

    public function config(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'initialView' => 'dayGridMonth',
            'editable' => true,
            'selectable' => false, // Disable date selection
            'dayMaxEvents' => true,
            'eventDurationEditable' => false, // Disable changing event duration by dragging
            'eventDrop' => 'function(info) {
                // Handle event drop (time change) here
                // You can implement this using Livewire or AJAX
            }',
            'eventClick' => 'function(info) {
                // Open a modal with event details
                // You can implement this using Filament or JavaScript
            }',
        ];
    }

}