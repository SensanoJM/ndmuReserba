<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Signatory;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Illuminate\Support\Str;

class ReservationTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $recordId;

    protected function getTableQuery(): Builder
    {
        return Booking::query()->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('user.name')
                ->label('User')
                ->searchable(),
            TextColumn::make('facility.facility_name')
                ->label('Facility')
                ->searchable(),
            TextColumn::make('booking_date')
                ->date()
                ->sortable(),
            TextColumn::make('start_time')
                ->time()
                ->sortable(),
            TextColumn::make('end_time')
                ->time()
                ->sortable(),
            TextColumn::make('purpose')
                ->limit(30),
            BadgeColumn::make('status')
                ->colors([
                    'info' => 'pending',
                    'success' => 'confirmed',
                    'danger' => 'canceled',
                ])
                ->formatStateUsing(fn($state) => match ($state) {
                    'pending' => 'Pending',
                    'confirmed' => 'Approved', // Display "Approved" instead of "Confirmed"
                    'canceled' => 'Denied', // Display "Denied" instead of "Canceled"
                }),
        ];
    }
    
    public function bookingInfolist(Booking $booking): Infolist
    {
        return Infolist::make()
            ->record($booking)  // Pass the booking record here
            ->schema([
                Section::make('Booking Details')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('User'),
                        TextEntry::make('facility.facility_name')
                            ->label('Facility'),
                        TextEntry::make('booking_date')
                            ->date(),
                        TextEntry::make('start_time')
                            ->time(),
                        TextEntry::make('end_time')
                            ->time(),
                        TextEntry::make('purpose'),
                        TextEntry::make('duration'),
                        TextEntry::make('participants'),
                        TextEntry::make('policy'),
                        TextEntry::make('equipment')
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'info',
                                'confirmed' => 'success',
                                'canceled' => 'danger',
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
            ->icon('heroicon-o-eye')
            ->modalContent(function (Booking $record) {
                // Use the bookingInfolist method to avoid code repetition
                return $this->bookingInfolist($record)->render();  // Call the existing bookingInfolist method
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(false),
            Action::make('approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn(Booking $record) => $this->approveBooking($record))
                ->visible(fn(Booking $record): bool => $record->status === 'pending'),
            Action::make('deny')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->action(fn(Booking $record) => $this->denyBooking($record))
                ->visible(fn(Booking $record): bool => $record->status === 'pending'),
        ];
    }

    public function approveBooking(Booking $booking)
    {
        $booking->update(['status' => 'in_review']);
        $this->createReservation($booking);
    }

    public function denyBooking(Booking $booking)
    {
        $booking->update(['status' => 'denied']);
    }

    //The createReservation method creates a new Reservation record and calls createSignatories.
    private function createReservation(Booking $booking)
    {
        $reservation = Reservation::create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'admin_approval_date' => now(),
        ]);

        $this->createSignatories($reservation);
    }

    // The createSignatories method creates Signatory records for each required approver, 
    // setting the order field. It then calls notifyNextSignatory.
    private function createSignatories(Reservation $reservation)
    {
        $signatoryRoles = ['adviser', 'dean', 'president'];

        foreach ($signatoryRoles as $role) {
            Signatory::create([
                'reservation_id' => $reservation->id,
                'role' => $role,
                'status' => 'pending',
                'approval_token' => Str::random(64),
            ]);
        }

        $this->notifySignatories($reservation);
    }

    // The notifySignatories method sends email notifications to all signatories simultaneously.
    private function notifySignatories(Reservation $reservation)
    {
        foreach ($reservation->signatories as $signatory) {
            // Here you would implement the logic to send an email notification
            // For example:
            // Mail::to($this->getSignatoryEmail($signatory->role))->send(new SignatoryApprovalRequest($reservation, $signatory));
        }
    }

    // To check if all signatories have approved. If so, it notifies the Director.
    private function getSignatoryEmail($role)
    {
        // Implement logic to get the email of the signatory based on their role
        // This could be a lookup in your User table or a predefined mapping
    }

    // Check if all signatories have approved the reservation.
    public function checkSignatoryApprovals(Reservation $reservation)
    {
        $allApproved = $reservation->signatories->every(function ($signatory) {
            return $signatory->status === 'approved';
        });

        if ($allApproved) {
            $this->notifyDirector($reservation);
        }
    }

    // Notify the Director of Students and Affairs Organization when all signatories have approved the reservation.
    private function notifyDirector(Reservation $reservation)
    {
        $director = User::where('role', 'signatory')
            ->where('position', 'Director of Students and Affairs Organization')
            ->first();

        if ($director) {
            // Notify the director
            // Mail::to($director->email)->send(new DirectorApprovalRequest($reservation));
        }
    }

    // The finalApproval method is called when the Director approves, finalizing the reservation.
    public function finalApproval(Reservation $reservation)
    {
        $reservation->update([
            'status' => 'approved',
            'final_approval_date' => now(),
        ]);

        $reservation->booking->update(['status' => 'approved']);

        // Notify the user that their booking has been fully approved
    }

    public function render()
    {
        return view('livewire.reservation-table');
    }
}
