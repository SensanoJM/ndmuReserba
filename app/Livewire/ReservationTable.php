<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Services\ReservationService;
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
use Filament\Tables\Filters\SelectFilter;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Columns\BadgeColumn;

class ReservationTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected $reservationService;
    
    public function boot(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->filters($this->getTableFilters())
            ->columns($this->getTableColumns())
            ->actions($this->getTableActions())
            ->striped()
            ->defaultSort('created_at', 'desc') 
            ->persistSortInSession()
            ->poll('5s')
            ->emptyStateHeading('No reservations found')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    protected function getTableQuery()
    {
        return Booking::withAllRelations()->latest()->tap(function ($query) {
        });
    }

    protected function getTableColumns(): array
    {
        return [
        TextColumn::make('user.name')
            ->label('Requester')
            ->searchable()
            ->sortable()
            ->toggleable(),
        TextColumn::make('purpose')
            ->limit(30)
            ->searchable()
            ->sortable()
            ->toggleable(),
        TextColumn::make('facility.facility_name')
            ->label('Facility')
            ->searchable()
            ->sortable()
            ->toggleable(),
        TextColumn::make('created_at')
            ->label('Request Date')
            ->dateTime()
            ->sortable()
            ->visible(fn(): bool => true)
            ->toggleable(),
        TextColumn::make('booking_start')
            ->label('Booking Start')
            ->dateTime()
            ->sortable()
            ->toggleable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn (Booking $record): string => match ($record->status) {
                    'prebooking' => 'gray',
                    'in_review' => 'warning',
                    'pending' => 'warning',
                    'approved' => 'success',
                    'denied' => 'danger',
                    default => 'secondary',
                })
                ->formatStateUsing(function ($state) {
                    return match ($state) {
                        'prebooking' => 'Pre-booking',
                        'in_review' => 'In Review',
                        'pending' => 'Pending Final Approval',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                        default => ucfirst($state),
                    };
                })
                ->toggleable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('view')
                    ->icon('heroicon-s-eye')
                    ->color('gray')
                    ->modalContent(fn (Booking $record) => $this->bookingInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Booking $record) {
                        $this->approveBooking($record);
                    })
                    ->visible(fn(Booking $record): bool => $this->canApprove($record)),
                Action::make('deny')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (Booking $record) {
                        $this->denyBooking($record);
                    })
                    ->requiresConfirmation()
                    ->visible(fn(Booking $record): bool => $this->canDeny($record)),
            ]),
        ];
    }

    public function bookingInfolist(Booking $booking): Infolist
    {
        return Infolist::make()
            ->record($booking)
            ->schema([
                Fieldset::make('Booking Details')
                    ->schema([
                        TextEntry::make('user.name')->label('User'),
                        TextEntry::make('facility.facility_name')->label('Facility'),
                        TextEntry::make('booking_start')->label('Start Time'),
                        TextEntry::make('booking_end')->label('End Time'),
                        TextEntry::make('purpose'),
                        TextEntry::make('participants'),
                    ])->columns(2),
                Fieldset::make('Approval Status')
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
                    ->hidden(fn(Booking $record) => $record->status === 'approved')
                    ->columns(2),
            ]);
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->options([
                    'prebooking' => 'Pre-booking',
                    'pending' => 'Pending',
                    'in_review' => 'In Review',
                    'approved' => 'Approved',
                    'denied' => 'Denied',
                ])
                ->label('Status')
                ->placeholder('All Statuses')
                ->multiple()
        ];
    }

    private function getPreBookingApprovalStatus(Booking $booking): array
    {
        if ($booking->status === 'pending' || $booking->status === 'in_review' || $booking->status === 'approved') {
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

        $signatory = $reservation->signatories->firstWhere('role', $role);
        return match ($signatory?->status) {
            'approved' => 'success',
            'denied' => 'danger',
            default => 'warning',
        };
    }

    private function canApprove(Booking $booking): bool
    {
        return $booking->status === 'prebooking' ||
            ($booking->status === 'pending' && $this->reservationService->allSignatoriesApproved($booking));
    }

    private function canDeny(Booking $booking): bool
    {
        return in_array($booking->status, ['prebooking', 'in_review', 'pending']);
    }

    public function approveBooking(Booking $booking)
    {
        if ($this->reservationService->approveBooking($booking)) {
            // Send notification to the booking owner
            Notification::make()
                ->title('Booking Status Updated')
                ->body($booking->status === 'prebooking' ? 
                    'Your booking is now under review by signatories.' : 
                    'Your booking has been approved.')
                ->icon($booking->status === 'prebooking' ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                ->iconColor($booking->status === 'prebooking' ? 'warning' : 'success')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.user.resources.bookings.view', $booking))
                ])
                ->sendToDatabase($booking->user);

            // Send success notification to admin
            Notification::make()
                ->success()
                ->title('Booking Approved')
                ->body('The booking status has been updated successfully.')
                ->send();

            $this->redirectToReservationTable();
        } else {
            Notification::make()
                ->danger()
                ->title('Approval Failed')
                ->body('There was an issue approving the booking.')
                ->send();
        }
    }

    public function denyBooking(Booking $booking)
    {
        if ($this->reservationService->denyBooking($booking)) {
            // Send notification to the booking owner
            Notification::make()
                ->title('Booking Denied')
                ->body('Your booking request has been denied.')
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.user.resources.bookings.view', $booking))
                ])
                ->sendToDatabase($booking->user);

            // Send success notification to admin
            Notification::make()
                ->warning()
                ->title('Booking Denied')
                ->body('The booking has been denied successfully.')
                ->send();

            $this->redirectToReservationTable();
        } else {
            Notification::make()
                ->danger()
                ->title('Action Failed')
                ->body('There was an issue denying the booking.')
                ->send();
        }
    }

    protected function redirectToReservationTable()
    {
        // Redirect to the current page to force a full refresh
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.reservation-table');
    }
}