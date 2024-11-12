<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Booking::class;

    public $isAvailable = true;
    public ?int $facilityFilter = null;

    public function boot()
    {
        // Get facility filter from session
        $this->facilityFilter = Session::get('calendar_facility_filter');
    }

    // Optional: Add a listener for filter changes
    protected $listeners = ['calendar-filter-changed' => 'handleFilterChange'];

    public function handleFilterChange($facilityId)
    {
        $this->facilityFilter = $facilityId;
        $this->refreshEvents();
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $query = Booking::query()
            ->with('user', 'facility')
            ->where('status', 'approved')
            ->whereBetween('booking_start', [$fetchInfo['start'], $fetchInfo['end']]);

        // Apply facility filter if set
        if ($this->facilityFilter) {
            $query->where('facility_id', $this->facilityFilter);
        }

        return $query->get()
            ->map(function (Booking $booking) {
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
                    ],
                ];
            })
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

    public function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('facility_id')
                ->label('Facility')
                ->options(Facility::pluck('facility_name', 'id'))
                ->required(),
            Forms\Components\TextInput::make('purpose')
                ->required(),
            DateTimePicker::make('booking_start')
                ->label('Start Time')
                ->required()
                ->minDate(now())
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    if ($state && Carbon::parse($state)->isPast()) {
                        $set('booking_start', null);
                        Notification::make()
                            ->title('Invalid Date')
                            ->body('You cannot book a date in the past.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->checkAvailability();
                    $this->updateDuration($get, $set);
                }),
            DateTimePicker::make('booking_end')
                ->label('End Time')
                ->required()
                ->minDate(now())
                ->reactive()
                ->after('booking_start')
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $this->checkAvailability();
                    $this->updateDuration($get, $set);
                }),
            Forms\Components\TextInput::make('participants')
                ->numeric()
                ->required(),
        ];
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

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
            ->mountUsing(function (Booking $record, Form $form, array $arguments) {
                $form->fill([
                    'facility_id' => $record->facility_id,
                    'purpose' => $record->purpose,
                    'booking_start' => $arguments['event']['start'] ?? $record->booking_start,
                    'booking_end' => $arguments['event']['end'] ?? $record->booking_end,
                    'participants' => $record->participants,
                ]);
            })
            ->action(function (Booking $record, array $data): void {
                // Store old dates for comparison
                $oldStart = $record->booking_start;
                $oldEnd = $record->booking_end;
                
                // Update the booking
                $record->update($data);
                
                // Check if dates were changed
                if ($oldStart != $data['booking_start'] || $oldEnd != $data['booking_end']) {
                    // Load the user relationship if not loaded
                    $record->load(['user', 'facility']);
                    
                    // Send notification to the booking requester
                    if ($record->user) {
                        $formattedStart = Carbon::parse($data['booking_start'])->format('M d, Y h:i A');
                        $formattedEnd = Carbon::parse($data['booking_end'])->format('M d, Y h:i A');
                        
                        Notification::make()
                            ->title('Booking Schedule Changed')
                            ->body("Your booking for {$record->facility->facility_name} has been rescheduled to:\n{$formattedStart} - {$formattedEnd}")
                            ->icon('heroicon-o-calendar')
                            ->iconColor('info')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->button()
                                    ->label('View Details')
                                    ->url(route('filament.user.pages.tracking-page'))
                            ])
                            ->sendToDatabase($record->user);
                    }
                }
                
                $this->refreshEvents();
                
                // Show success notification in UI for admin
                Notification::make()
                    ->title('Success')
                    ->body('Booking has been updated successfully.')
                    ->success()
                    ->send();
            }),
            Actions\DeleteAction::make()
                ->action(function (Booking $record): void {
                    $record->delete();
                    $this->refreshEvents();
                    Notification::make()
                        ->title('Booking deleted successfully')
                        ->success()
                        ->send();
                }),
        ];
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
            'selectable' => false,
            'dayMaxEvents' => true,
            'eventDurationEditable' => true,
            'eventDrop' => 'function(info) {
                $wire.eventDrop(info.event.id, info.event.start.toISOString(), info.event.end.toISOString());
            }',
            'selectConstraint' => [
                'start' => now()->startOfDay()->format('Y-m-d'), // Start from today
            ],
            'validRange' => [
                'start' => now()->startOfDay()->format('Y-m-d'), // Disable all dates before today
            ],
            'selectOverlap' => false, // Prevent selection of overlapping events
        ];
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

    /**
     * Update a booking event by dragging and dropping it to a new position in the calendar.
     *
     * @param int $eventId The ID of the booking event that was dragged and dropped.
     * @param string $newStart The new start date and time of the booking event in ISO 8601 format.
     * @param string $newEnd The new end date and time of the booking event in ISO 8601 format.
     */
    public function eventDrop($eventId, $newStart, $newEnd): void
    {
        // Eager load the booking with its relationships
        $booking = Booking::with(['user', 'facility'])->findOrFail($eventId);
        
        // Update the booking
        $booking->update([
            'booking_start' => $newStart,
            'booking_end' => $newEnd,
        ]);
    
        // Notify the booking requester
        if ($booking->user) {
            $formattedStart = Carbon::parse($newStart)->format('M d, Y h:i A');
            $formattedEnd = Carbon::parse($newEnd)->format('M d, Y h:i A');
            
            Notification::make()
                ->title('Booking Schedule Changed')
                ->body("Your booking for {$booking->facility->facility_name} has been rescheduled to:\n{$formattedStart} - {$formattedEnd}")
                ->icon('heroicon-o-calendar')
                ->iconColor('info')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->label('View Details')
                        ->url(route('filament.user.pages.tracking-page'))
                ])
                ->sendToDatabase($booking->user);
        }
    
        $this->refreshEvents();
        
        // Show immediate success notification in UI for admin
        Notification::make()
            ->title('Success')
            ->body('Booking schedule has been updated successfully.')
            ->success()
            ->send();
    }

    /**
     * Dispatches an event to refresh the calendar events.
     *
     * @internal This should only be called internally by this class.
     */
    private function refreshEvents(): void
    {
        $this->dispatch('eventRefresh');
    }
}
