<?php

namespace App\Livewire;

use App\Jobs\SendSignatoryEmailsJob;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Signatory;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Component;

class ReservationTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $recordId;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions($this->getTableActions());
    }

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
                    'warning' => 'in_review',
                    'success' => 'confirmed',
                    'danger' => 'canceled',
                ])
                ->formatStateUsing(fn($state) => match ($state) {
                    'pending' => 'Pending',
                    'in_review' => 'In Review',
                    'confirmed' => 'Approved', // Display "Approved" instead of "Confirmed"
                    'canceled' => 'Denied', // Display "Denied" instead of "Canceled"
                }),
        ];
    }

    public function bookingInfolist(Booking $booking): Infolist
    {
        return Infolist::make()
            ->record($booking) // Pass the booking record here
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
                                'in_review' => 'warning',
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
                    return $this->bookingInfolist($record)->render();
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

    /**
     * Approve a booking request.
     *
     * If the booking is pending, and no reservation has been created yet,
     * update the booking status to "in_review", create a new reservation,
     * and send a "Booking Approved" notification.
     *
     * If the booking has already been approved, or a reservation already exists,
     * send a "Booking Already Approved" notification.
     *
     * Added a check to prevent creating duplicate reservations.
     *
     * @param Booking $booking The booking to approve
     * @return void
     */
    public function approveBooking(Booking $booking)
    {
        // Check if a reservation already exists for this booking
        $existingReservation = Reservation::where('booking_id', $booking->id)->first();

        if (!$existingReservation) {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'in_review']);
                $this->createReservation($booking);
            });

            Notification::make()
                ->title('Booking Approved')
                ->success()
                ->send();

            // Refresh the table data
            $this->dispatch('refreshTable');
        } else {
            Notification::make()
                ->title('Booking Already Approved')
                ->warning()
                ->send();
        }
    }

    #[On('refreshTable')]
    public function refreshTable()
    {
        // This method will be called when the 'refreshTable' event is dispatched
        // The table will automatically refresh due to Livewire's reactivity
    }

    // Deny a booking request.
    public function denyBooking(Booking $booking)
    {
        $booking->update(['status' => 'denied']);

        Notification::make()
            ->title('Booking Denied')
            ->danger()
            ->send();

        $this->render();
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

        return $reservation;
    }

    /**
     * Create Signatory records for a Reservation and dispatch the job to send emails.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    private function createSignatories(Reservation $reservation)
    {
        $booking = $reservation->booking;
        $signatoryRoles = [
            'adviser' => $booking->adviser_email,
            'dean' => $booking->dean_email,
            'school_president' => $this->getSchoolPresidentEmail(),
        ];

        foreach ($signatoryRoles as $role => $email) {
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email for role {$role}: {$email}");
            }

            // Check if there's a corresponding user account
            $userId = User::where('email', $email)->value('id');

            Signatory::updateOrCreate(
                [
                    'reservation_id' => $reservation->id,
                    'role' => $role,
                ],
                [
                    'email' => $email,
                    'user_id' => $userId, // This can be null if no matching user is found
                    'status' => 'pending',
                    'approval_token' => Str::random(64),
                ]
            );
        }

        // Dispatch the job to send emails
        SendSignatoryEmailsJob::dispatch($reservation);
    }

    private function getSchoolPresidentEmail()
    {
        return User::where('role', 'signatory')
            ->where('position', 'school_president')
            ->value('email');
    }

    // Check if all signatories have approved the reservation.
    public function checkSignatoryApprovals(Reservation $reservation)
    {
        $allApproved = $reservation->signatories->every(function ($signatory) {
            return $signatory->status === 'approved';
        });

        if ($allApproved) {
            $reservation->update(['status' => 'pending']);
            // The Director notification is now handled by the ReservationObserver
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
        // You might want to add a notification or email to the user here
    }

    public function render()
    {
        return view('livewire.reservation-table');
    }
}
