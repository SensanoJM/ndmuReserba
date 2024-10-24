<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Facility;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Booking::class;

    public function fetchEvents(array $fetchInfo): array
    {
        return Booking::query()
            ->with('user', 'facility')
            ->where('status', 'approved')
            ->whereBetween('booking_start', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
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
            Forms\Components\DateTimePicker::make('booking_start')
                ->label('Start Date and Time')
                ->required(),
            Forms\Components\DateTimePicker::make('booking_end')
                ->label('End Date and Time')
                ->required(),
            Forms\Components\TextInput::make('participants')
                ->numeric()
                ->required(),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mountUsing(
                    function (Booking $record, Form $form, array $arguments) {
                        $form->fill([
                            'facility_id' => $record->facility_id,
                            'purpose' => $record->purpose,
                            'booking_start' => $arguments['event']['start'] ?? $record->booking_start,
                            'booking_end' => $arguments['event']['end'] ?? $record->booking_end,
                            'participants' => $record->participants,
                        ]);
                    }
                )
                ->action(function (Booking $record, array $data): void {
                    $record->update($data);
                    $this->refreshEvents();
                    Notification::make()
                        ->title('Booking updated successfully')
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
            'selectable' => true,
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

    /**
     * Updates a booking with new start and end dates after a user has dropped it in the calendar.
     *
     * @param int $eventId The ID of the booking that was moved.
     * @param string $newStart The new start date of the booking.
     * @param string $newEnd The new end date of the booking.
     */
    public function eventDrop($eventId, $newStart, $newEnd): void
    {
        $booking = Booking::findOrFail($eventId);
        $booking->update([
            'booking_start' => $newStart,
            'booking_end' => $newEnd,
        ]);
        $this->refreshEvents();
        Notification::make()
            ->title('Booking updated successfully')
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