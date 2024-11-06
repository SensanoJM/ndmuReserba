<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Facility;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class UserCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Booking::class;

    public $isAvailable = true;

    protected $equipmentOptions = [
        'plastic_chairs' => 'Plastic Chairs',
        'long_table' => 'Long Table',
        'teacher_table' => 'Teacher\'s Table',
        'backdrop' => 'Backdrop',
        'riser' => 'Riser',
        'armed_chair' => 'Armed Chairs',
        'pole' => 'Pole',
        'rostrum' => 'Rostrum',
    ];

    public function fetchEvents(array $fetchInfo): array
    {
        // Get current user's ID
        $userId = Auth::id();
        
        return Booking::query()
            ->with('user', 'facility')
            ->where('status', 'approved')
            ->whereBetween('booking_start', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(function (Booking $booking) use ($userId) {
                // Check if this is the user's booking
                $isUserBooking = $booking->user_id === $userId;
                
                // If this is a newly updated booking for this user and hasn't been notified
                if ($isUserBooking && $booking->wasChanged('booking_start', 'booking_end') && !$booking->updateNotificationSent) {
                    Notification::make()
                        ->title('Your Booking Has Been Updated')
                        ->body('The schedule for your booking has been modified. Click below to view the details.')
                        ->icon('heroicon-o-calendar')
                        ->iconColor('info')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->button()
                                ->label('View Details')
                                ->url(route('filament.user.pages.tracking-page'))
                        ])
                        ->sendToDatabase($booking->user);
    
                    // Mark notification as sent
                    $booking->update(['updateNotificationSent' => true]);
                }
    
                return [
                    'id' => $booking->id,
                    'title' => $booking->purpose,
                    'start' => $booking->booking_start,
                    'end' => $booking->booking_end,
                    'allDay' => false,
                    'backgroundColor' => $this->getEventColor($booking->user->role),
                    'extendedProps' => [
                        'user' => $booking->user->name,
                        'facility' => $booking->facility->facility_name,
                        'isUserBooking' => $isUserBooking
                    ],
                    'editable' => false,
                    'startEditable' => false,
                    'durationEditable' => false,
                    'resourceEditable' => false,
                    'display' => 'block'
                ];
            })
            ->toArray();
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function updateDuration(Get $get, Set $set): void
    {
        $start = $get('booking_start');
        $end = $get('booking_end');

        if ($start && $end) {
            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            if ($endTime->lte($startTime)) {
                $set('booking_end', null);
                Notification::make()
                    ->title('Invalid Time')
                    ->body('End time must be after start time.')
                    ->danger()
                    ->send();
                return;
            }

            // Calculate the duration
            $duration = $this->calculateDuration($startTime, $endTime);
            $set('duration', $duration);
        }
    }

    protected function calculateDuration(Carbon $start, Carbon $end): string
    {
        $totalMinutes = $start->diffInMinutes($end);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $durationParts = [];

        if ($hours > 0) {
            $durationParts[] = "{$hours} " . ($hours === 1 ? 'hour' : 'hours');
        }

        if ($minutes > 0) {
            $durationParts[] = "{$minutes} " . ($minutes === 1 ? 'minute' : 'minutes');
        }

        return implode(' ', $durationParts);
    }

    public function checkAvailability()
    {
        // Check if booking_start and booking_end are set
        if (!isset($this->data['booking_start']) || !isset($this->data['booking_end'])) {
            $this->isAvailable = true;
            return;
        }

        // Check if booking_start and booking_end are not empty
        if (empty($this->data['booking_start']) || empty($this->data['booking_end'])) {
            $this->isAvailable = true;
            return;
        }

        // Proceed with availability check
        $this->isAvailable = $this->facilityRepository->checkAvailability(
            $this->selectedFacility,
            $this->data['booking_start'],
            $this->data['booking_end']
        );

        $this->notifyAvailability();
    }

    protected function notifyAvailability()
    {
        $notification = Notification::make()
            ->title($this->isAvailable ? 'Time Slot Available' : 'Time Slot Unavailable')
            ->body($this->isAvailable ? 'The selected time slot is available.' : 'The selected time slot is not available. Please choose a different time.')
            ->icon($this->isAvailable ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->iconColor($this->isAvailable ? 'success' : 'danger');

        $notification->send();
    }

    public function getEventColor(string $userRole): string
    {
        return match ($userRole) {
            'organization' => '#4CAF50',
            'faculty' => '#2196F3',
            'admin' => '#FF5722',
            default => '#9C27B0',
        };
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
            'editable' => false,
            'selectable' => false,
            'dayMaxEvents' => true,
            'eventDurationEditable' => false,
            'selectOverlap' => false, // Prevent selection of overlapping events
            'eventClick' => false, // Disable event clicking
            'eventStartEditable' => false, // Prevent event dragging
            'eventResizeable' => false, // Prevent event resizing
            'eventInteractive' => false, // Make events non-interactive
            'eventClick' => 'function(info) { return false; }', // This prevents the modal from showing up
            'events' => true, // Keep events visible but non-interactive
        ];
    }

    // Override the modal component to be null
    public function getModalComponent(): ?string
    {
        return null;
    }

    // Disable all default actions
    protected function getActions(): array
    {
        return [];
    }

    // Override the event click handler
    public function onEventClick($event): void
    {
        // Do nothing
    }

    // Disable interaction with events
    protected function modalActions(): array
    {
        return [];
    }

    public function eventDidMount(): string
    {
        return <<<JS
        function({ event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view }) {
            const startTime = new Date(event.start).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit'
            });
            const endTime = new Date(event.end).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit'
            });
            
            const tooltipContent = 
                "📝 " + event.title + "  " +
                "🕒 " + startTime + " - " + endTime + "  " +
                "🏢 " + event.extendedProps.facility + "  " +
                "👤 " + event.extendedProps.user;
            
            el.setAttribute("x-tooltip", "tooltip");
            el.setAttribute("x-data", "{ tooltip: " + JSON.stringify(tooltipContent) + " }");
        }
        JS;
    }

    private function refreshEvents(): void
    {
        $this->dispatch('eventRefresh');
    }
}
