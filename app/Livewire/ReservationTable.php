<?php

namespace App\Livewire;

use App\Jobs\SendSignatoryEmailsJob;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Signatory;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

class ReservationTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $recordId;
    public $activeTab = 'all';
    protected $listeners = ['tabChanged' => 'updateActiveTab'];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions($this->getTableActions())
            ->poll('10s'); // Poll every 10 seconds for updates
    }

    /**
     * Gets the query for the table.
     *
     * Depending on the active tab, filters the bookings by status.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getTableQuery(): Builder
    {
        $query = Booking::query();

        switch ($this->activeTab) {
            case 'pending':
                $query->where('status', 'pending');
                break;
            case 'in_review':
                $query->where('status', 'in_review');
                break;
            case 'approved':
                $query->where('status', 'approved');
                break;
            case 'denied':
                $query->where('status', 'denied');
                break;
            case 'all':
            default:
                // No filtering for 'all' tab
                break;
        }

        return $query->latest();
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
                    'primary' => 'approved',
                    'danger' => 'denied',
                ])
                ->formatStateUsing(fn($state) => match ($state) {
                    'pending' => 'Pending',
                    'in_review' => 'In Review',
                    'approved' => 'Approved', // Display "Approved" instead of "Confirmed"
                    'denied' => 'Denied', // Display "Denied" instead of "Canceled"
                }),
        ];
    }

    public function bookingInfolist(Booking $booking): Infolist
    {
        return Infolist::make()
            ->record($booking)
            ->schema([
                Section::make('General Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Booked by')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('facility.facility_name')
                            ->label('Facility')
                            ->icon('heroicon-o-building-office'),
                        TextEntry::make('purpose')
                            ->icon('heroicon-o-clipboard-document-list'),
                        TextEntry::make('status')
                            ->badge()
                            ->icon('heroicon-o-clock')
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'info',
                                'in_review' => 'warning',
                                'approved' => 'success',
                                'denied' => 'danger',
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Date & Time')
                    ->schema([
                        TextEntry::make('booking_date')
                            ->date()
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('start_time')
                            ->time()
                            ->icon('heroicon-o-clock')
                            ->label('Start Time'),
                        TextEntry::make('duration')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('end_time')
                            ->time()
                            ->icon('heroicon-o-clock')
                            ->label('End Time'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Additional Details')
                    ->schema([
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                        TextEntry::make('equipment')
                            ->placeholder('No equipment provided')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return 'No equipment requested';
                                }

                                $equipment = is_string($state) ? json_decode($state, true) : $state;

                                if (!is_array($equipment)) {
                                    return 'Invalid equipment data';
                                }

                                $equipmentLabels = [
                                    'plastic_chairs' => 'Plastic Chairs',
                                    'long_table' => 'Long Table',
                                    'teacher_table' => 'Teacher\'s Table',
                                    'backdrop' => 'Backdrop',
                                    'riser' => 'Riser',
                                    'armed_chair' => 'Armed Chairs',
                                    'pole' => 'Pole',
                                    'rostrum' => 'Rostrum',
                                ];

                                $formattedEquipment = collect($equipment)
                                    ->map(function ($quantity, $item) use ($equipmentLabels) {
                                        $label = $equipmentLabels[$item] ?? ucfirst(str_replace('_', ' ', $item));
                                        return "{$label}: {$quantity}";
                                    })
                                    ->join(', ');

                                return $formattedEquipment ?: 'No equipment requested';
                            }),
                        TextEntry::make('policy')
                            ->markdown()
                            ->placeholder('No policy provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Approval Details')
                    ->schema([
                        TextEntry::make('formatted_signatories')
                            ->listWithLineBreaks()
                            ->placeholder('No approvals yet')
                            ->formatStateUsing(fn($state) => $state),
                    ])
                    ->collapsible(),

                Section::make('Attachments')
                    ->schema([
                        TextEntry::make('booking_attachments')
                            ->listWithLineBreaks()
                            ->placeholder('No files attached')
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state)) {
                                    return $state;
                                }

                                return collect($state)->map(function ($path) {
                                    $fileName = basename($path);
                                    $url = \Illuminate\Support\Facades\Storage::url($path);
                                    return "<a href='{$url}' target='_blank'>{$fileName}</a>";
                                })->toArray();
                            })
                            ->html(),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->modalContent(function (Booking $record) {
                        return $this->bookingInfolist($record)->render();
                    })
                    ->slideOver(true)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn(Booking $record) => $this->approveBooking($record))
                    ->requiresConfirmation()
                    ->visible(fn(Booking $record): bool => $this->canApprove($record)),
                Action::make('deny')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn(Booking $record) => $this->denyBooking($record))
                    ->requiresConfirmation()
                    ->visible(fn(Booking $record): bool => $this->canDeny($record)),
            ]),
        ];
    }

    private function canApprove(Booking $booking): bool
    {
        return $booking->status === 'pending' ||
            ($booking->status === 'in_review' && $this->allSignatoriesApproved($booking));
    }

    private function canDeny(Booking $booking): bool
    {
        return $booking->status === 'pending' || $booking->status === 'in_review';
    }

    private function allSignatoriesApproved(Booking $booking): bool
    {
        return $booking->reservation->signatories()->where('status', '!=', 'approved')->doesntExist();
    }

    // Deny a booking request.
    public function denyBooking(Booking $booking)
    {
        $booking->update(['status' => 'denied']);
        if ($booking->reservation) {
            $booking->reservation->update(['status' => 'denied']);
        }

        Notification::make()
            ->title('Booking Denied')
            ->danger()
            ->send();

        // Notify the user
        Notification::make()
            ->title('Your booking has been denied')
            ->danger()
            ->sendToDatabase($booking->user);

        $this->refreshTable();
        // Dispatch an event to refresh both table and tabs
        $this->dispatch('bookingStatusChanged');
    }

    public function approveBooking(Booking $booking)
    {
        if ($booking->status === 'pending') {
            $this->initialApprove($booking);
        } elseif ($booking->status === 'in_review' && $this->allSignatoriesApproved($booking)) {
            $this->finalApprove($booking);
        }

        $this->refreshTable();
        // Dispatch an event to refresh both table and tabs
        $this->dispatch('bookingStatusChanged');
    }

    private function initialApprove(Booking $booking)
    {
        $booking->update(['status' => 'in_review']);
        $reservation = $booking->reservation()->create(['status' => 'pending']);
        $this->createSignatories($reservation);

        Notification::make()
            ->title('Booking Initially Approved')
            ->success()
            ->send();
    }

    // This will trigger a re-render of the component
    #[On('bookingStatusChanged')]
    public function refreshTable()
    {
        // The table will automatically refresh due to Livewire's reactivity
    }

    // This allows the table to refresh when the active tab is changed.
    #[On('tabChanged')]
    public function updateActiveTab($tabId)
    {
        $this->activeTab = $tabId;
        $this->refreshTable();
    }

    private function finalApprove(Booking $booking)
    {
        $booking->update(['status' => 'approved']);
        $booking->reservation->update(['status' => 'approved', 'final_approval_date' => now()]);

        Notification::make()
            ->title('Booking Finally Approved')
            ->success()
            ->send();

        // Notify the user
        Notification::make()
            ->title('Your booking has been approved')
            ->success()
            ->sendToDatabase($booking->user);

        $this->refreshTable();
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
            'school_director' => $this->getSchoolDirectorEmail(),
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
                    'approval_token' => Str::random(32),
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

    private function getSchoolDirectorEmail()
    {
        return User::where('role', 'signatory')
            ->where('position', 'school_director')
            ->value('email');
    }

    public function render()
    {
        return view('livewire.reservation-table');
    }
}
