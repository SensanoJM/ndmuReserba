<?php

namespace App\Livewire;

use App\Jobs\SendSignatoryEmailsJob;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Signatory;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

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
            ->poll('10s')
            ->reorderable('updated_at');
    }

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

        return Booking::query()
            ->with(['user', 'facility', 'reservation.signatories', 'equipment', 'attachments', 'approvers'])
            ->when($this->activeTab !== 'all', function ($query) {
                return $query->where('status', $this->activeTab);
            })
            ->latest();
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
            TextColumn::make('booking_start')
                ->sortable()
                ->dateTime(),
            TextColumn::make('booking_end')
                ->sortable()
                ->dateTime(),
            TextColumn::make('purpose')
                ->limit(30),
            TextColumn::make('status')
                ->badge()
                ->color(function (Booking $record) {
                    $status = $record->fresh()->status;
                    $color = $this->getStatusColor($status);
                    Log::debug('Status column color:', ['booking_id' => $record->id, 'status' => $status, 'color' => $color]);
                    return $color;
                })
                ->formatStateUsing(function (Booking $record) {
                    $status = $record->fresh()->status;
                    Log::debug('Status column text:', ['booking_id' => $record->id, 'status' => $status]);
                    return ucfirst($status);
                })
        ];
    }

    public function bookingInfolist(Booking $booking): Infolist
    {
        return Infolist::make()
            ->record($booking)
            ->schema([
                Fieldset::make('Additional Details')
                    ->schema([
                        TextEntry::make('facility.facility_name')
                            ->icon('heroicon-o-building-office-2')
                            ->label('Facility'),
                        TextEntry::make('purpose')
                            ->icon('heroicon-o-calendar-days')
                            ->label('Purpose'),
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                        TextEntry::make('equipment')
                            ->placeholder('No Equipment')
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn($state, Booking $record) =>
                                $record->equipment->map(fn($equipment) =>
                                    "{$equipment->name}: {$equipment->pivot->quantity}"
                                )->join(', ')
                            ),
                    ])
                    ->columns(2),
                Fieldset::make('Booking Date')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Pre-booking Approval')
                            ->formatStateUsing(function (string $state, Booking $record) {
                                $status = $this->getPreBookingApprovalStatus($record);
                                return $status['status'] . ($status['date'] ? ' on ' . $status['date']->format('Y-m-d H:i') : '');
                            })
                            ->icon(function (string $state, Booking $record) {
                                return $this->getPreBookingApprovalStatus($record)['icon'];
                            })
                            ->iconColor(function (string $state, Booking $record) {
                                return $this->getPreBookingApprovalStatus($record)['color'];
                            })
                            ->placeholder('Pending'),
                        TextEntry::make('reservation.signatories')
                            ->label('Adviser Approval')
                            ->formatStateUsing(fn($state, Booking $record) => $this->formatSignatoryStatus($record->reservation, 'adviser'))
                            ->icon(fn($state, Booking $record) => $this->getSignatoryIcon($record->reservation, 'adviser'))
                            ->iconColor(fn($state, Booking $record) => $this->getSignatoryColor($record->reservation, 'adviser'))
                            ->placeholder('Pending'),
                        TextEntry::make('reservation.signatories')
                            ->label('Dean Approval')
                            ->formatStateUsing(fn($state, Booking $record) => $this->formatSignatoryStatus($record->reservation, 'dean'))
                            ->icon(fn($state, Booking $record) => $this->getSignatoryIcon($record->reservation, 'dean'))
                            ->iconColor(fn($state, Booking $record) => $this->getSignatoryColor($record->reservation, 'dean'))
                            ->placeholder('Pending'),
                        TextEntry::make('reservation.signatories')
                            ->label('School President Approval')
                            ->formatStateUsing(fn($state, Booking $record) => $this->formatSignatoryStatus($record->reservation, 'school_president'))
                            ->icon(fn($state, Booking $record) => $this->getSignatoryIcon($record->reservation, 'school_president'))
                            ->iconColor(fn($state, Booking $record) => $this->getSignatoryColor($record->reservation, 'school_president'))
                            ->placeholder('Pending'),
                        TextEntry::make('reservation.signatories')
                            ->label('School Director Approval')
                            ->formatStateUsing(fn($state, Booking $record) => $this->formatSignatoryStatus($record->reservation, 'school_director'))
                            ->icon(fn($state, Booking $record) => $this->getSignatoryIcon($record->reservation, 'school_director'))
                            ->iconColor(fn($state, Booking $record) => $this->getSignatoryColor($record->reservation, 'school_director'))
                            ->placeholder('Pending'),
                    ])
                    ->columns(2),
                Fieldset::make('Attachments')
                    ->schema([
                        TextEntry::make('attachments')
                            ->placeholder('Attached Files')
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn($state, Booking $record) =>
                                $this->formatAttachments($record)
                            )
                            ->html(),
                    ]),
            ]);
    }

    private function formatAttachments(Booking $booking): string
    {
        return $booking->attachments->map(function ($attachment) {
            $url = \Illuminate\Support\Facades\Storage::url($attachment->file_path);
            return "<a href='{$url}' target='_blank'>{$attachment->file_name}</a>";
        })->join("\n");
    }

    private function getPreBookingApprovalStatus(Booking $booking): array
    {
        if ($booking->status === 'in_review' || $booking->status === 'approved') {
            return [
                'status' => 'Approved',
                'date' => $booking->reservation->admin_approval_date ?? now(),
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ];
        }

        return [
            'status' => 'Pending',
            'date' => null,
            'icon' => 'heroicon-o-clock',
            'color' => 'warning',
        ];
    }

    private function formatSignatoryStatus($reservation, $role): string
    {
        if (!$reservation) {
            return 'Pending';
        }

        if ($role === 'prebooking') {
            return $reservation->admin_approval_date
            ? 'Approved on ' . $reservation->admin_approval_date->format('Y-m-d H:i')
            : 'Pending';
        }

        $signatory = $reservation->signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'Pending';
        }

        return match ($signatory->status) {
            'approved' => 'Approved on ' . $signatory->approval_date->format('Y-m-d H:i'),
            'denied' => 'Denied on ' . $signatory->approval_date->format('Y-m-d H:i'),
            default => 'Pending',
        };
    }

    private function getSignatoryIcon($reservation, $role): string
    {
        if (!$reservation) {
            return 'heroicon-o-clock';
        }

        if ($role === 'prebooking') {
            return $reservation->admin_approval_date ? 'heroicon-o-check-circle' : 'heroicon-o-clock';
        }

        $signatory = $reservation->signatories->firstWhere('role', $role);
        return match ($signatory?->status) {
            'approved' => 'heroicon-o-check-circle',
            'denied' => 'heroicon-o-x-circle',
            default => 'heroicon-o-clock',
        };
    }

    private function getSignatoryColor($reservation, $role): string
    {
        if (!$reservation) {
            return 'warning';
        }

        if ($role === 'prebooking') {
            return $reservation->admin_approval_date ? 'success' : 'warning';
        }

        $signatory = $reservation->signatories->firstWhere('role', $role);
        return match ($signatory?->status) {
            'approved' => 'success',
            'denied' => 'danger',
            default => 'warning',
        };
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('view')
                    ->icon('heroicon-s-eye')
                    ->color('info')
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
                    ->modalIcon('heroicon-o-check')
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

    protected function getStatusColor(string $status): string
    {
        Log::debug('getStatusColor called with status:', ['status' => $status, 'type' => gettype($status)]);
            // Ensure we're working with a string
        $statusString = is_string($status) ? $status : $status->value;

        return match ($status) {
            'pending' => 'info',
            'in_review' => 'warning',
            'approved' => 'success',
            'denied' => 'danger',
            default => 'info',
        };

        Log::debug('Color determined:', ['status' => $statusString, 'color' => $color]);

        return $color;
    }

    public function refreshTable()
    {
        $this->resetTable();
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

    public function denyBooking(Booking $booking)
    {
        $booking->status = 'denied'; // or 'approved', or 'denied'
        $booking->save();
        if ($booking->reservation) {
            $booking->reservation->update(['status' => 'denied']);
        }

        $this->refreshTable();
        $this->dispatch('bookingStatusChanged');

        Notification::make()
            ->title('Booking Denied')
            ->danger()
            ->send();

        Notification::make()
            ->title('Your booking has been denied')
            ->danger()
            ->sendToDatabase($booking->user);
    }

    public function approveBooking(Booking $booking)
    {
        Log::debug('Approving booking - start', ['booking_id' => $booking->id, 'current_status' => $booking->status]);
        if ($booking->status === 'pending') {
            $booking->status = 'in_review'; // or 'approved', or 'denied'
            $booking->save();
            $this->initialApprove($booking);
        } elseif ($booking->status === 'in_review' && $this->allSignatoriesApproved($booking)) {
            $this->finalApprove($booking);
        }

        Log::debug('Approving booking - end', ['booking_id' => $booking->id, 'new_status' => $booking->fresh()->status]);

        $this->dispatch('bookingStatusChanged', bookingId: $booking->id);
    }

    /**
     * Refresh the table when the booking status is changed.
     *
     * This is a hook for the 'bookingStatusChanged' event. When the event is fired, this method will trigger a re-render of the component, which will cause the table to be re-computed based on the new booking status.
     *
     * @return void
     */
    #[On('bookingStatusChanged')]
    public function handleBookingStatusChanged($bookingId)
    {
        Log::debug('bookingStatusChanged event received', ['booking_id' => $bookingId]);
        $this->dispatch('refresh-page');
    }

/**
 * Begin the initial approval process for a booking.
 *
 * This function updates the booking status to 'in_review', creates a reservation with status 'pending'
 * and sets the admin approval date to the current datetime. It then creates signatories for the reservation
 * and dispatches a job to send signatory emails. If the process is successful, a success notification is sent.
 *
 * @param Booking $booking The booking to be initially approved
 */
    private function initialApprove(Booking $booking)
    {
        try {
            DB::beginTransaction();
            \Illuminate\Support\Facades\Log::info('Booking status updated to in_review', ['booking_id' => $booking->id]);

            $reservation = $booking->reservation()->create([
                'status' => 'pending',
                'admin_approval_date' => now(),
            ]);

            if (!$reservation) {
                throw new \Exception('Failed to create reservation');
            }

            \Illuminate\Support\Facades\Log::info('Reservation created', ['reservation_id' => $reservation->id, 'booking_id' => $booking->id]);

            $this->createSignatories($reservation);

            // Dispatch the job to send emails
            SendSignatoryEmailsJob::dispatch($reservation);

            DB::commit();

            Notification::make()
                ->title('Booking Initially Approved')
                ->success()
                ->send();

            \Illuminate\Support\Facades\Log::info('Initial approval process completed successfully', ['booking_id' => $booking->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error in initialApprove', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error in Initial Approval')
                ->body('An error occurred while processing the initial approval. Please try again.')
                ->danger()
                ->send();
        }
    }

    #[On('tabChanged')]
    public function updateActiveTab($tabId)
    {
        $this->activeTab = $tabId;
    }

    private function finalApprove(Booking $booking)
    {
        $booking->update(['status' => 'approved']);
        $booking->reservation->update(['status' => 'approved', 'final_approval_date' => now()]);

        Notification::make()
            ->title('Booking Finally Approved')
            ->success()
            ->send();

        Notification::make()
            ->title('Your booking has been approved')
            ->success()
            ->sendToDatabase($booking->user);

        $this->dispatch('refresh-page');
    }

    /**
     * Create signatories for the given reservation.
     *
     * @param  \App\Models\Reservation|null  $reservation
     * @return void
     */
    private function createSignatories(?Reservation $reservation)
    {
        if (!$reservation) {
            \Illuminate\Support\Facades\Log::error('Attempted to create signatories for null reservation');
            return;
        }

        \Illuminate\Support\Facades\Log::info('Creating signatories for reservation', ['reservation_id' => $reservation->id]);

        try {
            $booking = $reservation->booking;
            $signatoryRoles = [
                'adviser' => $booking->approvers->where('role', 'adviser')->first()->email,
                'dean' => $booking->approvers->where('role', 'dean')->first()->email,
                'school_president' => $this->getSchoolPresidentEmail(),
                'school_director' => $this->getSchoolDirectorEmail(),
            ];

            foreach ($signatoryRoles as $role => $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    \Illuminate\Support\Facades\Log::warning("Invalid email for role {$role}", ['email' => $email]);
                    continue;
                }

                $userId = User::where('email', $email)->value('id');

                Signatory::create([
                    'reservation_id' => $reservation->id,
                    'role' => $role,
                    'email' => $email,
                    'user_id' => $userId,
                    'status' => 'pending',
                    'approval_token' => Str::random(32),
                ]);

                \Illuminate\Support\Facades\Log::info("Signatory created for role {$role}", ['email' => $email]);
            }

            \Illuminate\Support\Facades\Log::info('All signatories created successfully', ['reservation_id' => $reservation->id]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating signatories', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

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
