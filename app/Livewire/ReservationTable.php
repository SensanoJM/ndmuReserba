<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Services\ReservationService;
use Carbon\Carbon;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;

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
                ->formatStateUsing(fn ($state) => Carbon::parse($state)
                    ->setTimezone('Asia/Manila')
                    ->format('M d, Y h:i A'))
                ->sortable()
                ->visible(fn(): bool => true)
                ->toggleable(),
            TextColumn::make('booking_start')
                ->label('Booking Start')
                ->formatStateUsing(fn ($state) => Carbon::parse($state)
                    ->setTimezone('Asia/Manila')
                    ->format('M d, Y h:i A'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn(Booking $record): string => match ($record->status) {
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
                    ->modalContent(fn(Booking $record) => $this->bookingInfolist($record))
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

    public function bookingInfolist(Booking $record): Infolist
    {
            // Ensure relationships are loaded
            $record->load(['reservation.signatories', 'facility', 'user']);

            return Infolist::make()
            ->record($record)
            ->schema([
                Fieldset::make('Booking Details')
                    ->schema([
                        TextEntry::make('user.name')
                            ->icon('heroicon-o-user')
                            ->label('User'),
                        TextEntry::make('facility.facility_name')
                            ->label('Facility')
                            ->icon('heroicon-o-building-office'),
                        TextEntry::make('booking_start')
                            ->label('Start Date')
                            ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Manila')->format('M d, Y h:i A'))
                            ->iconColor('primary')
                            ->icon('heroicon-o-calendar'),
                    TextEntry::make('booking_end')
                            ->label('End Date')
                            ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Manila')->format('M d, Y h:i A'))
                            ->iconColor('warning')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('purpose')
                            ->icon('heroicon-o-pencil'),
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                    ])->columns(2),

                Fieldset::make('Approval Progress')
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
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'adviser'))
                            ->icon(fn($state, $record) => $this->getSignatoryIcon($record->reservation->signatories, 'adviser'))
                            ->iconColor(fn($state, $record) => $this->getSignatoryColor($record->reservation->signatories, 'adviser')),
                        TextEntry::make('reservation.signatories')
                            ->label('Dean Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'dean'))
                            ->icon(fn($state, $record) => $this->getSignatoryIcon($record->reservation->signatories, 'dean'))
                            ->iconColor(fn($state, $record) => $this->getSignatoryColor($record->reservation->signatories, 'dean')),
                        TextEntry::make('reservation.signatories')
                            ->label('School President Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'school_president'))
                            ->icon(fn($state, $record) => $this->getSignatoryIcon($record->reservation->signatories, 'school_president'))
                            ->iconColor(fn($state, $record) => $this->getSignatoryColor($record->reservation->signatories, 'school_president')),
                        TextEntry::make('reservation.signatories')
                            ->label('School Director Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'school_director'))
                            ->icon(fn($state, $record) => $this->getSignatoryIcon($record->reservation->signatories, 'school_director'))
                            ->iconColor(fn($state, $record) => $this->getSignatoryColor($record->reservation->signatories, 'school_director')),
                    ])
                    ->hidden(fn(Booking $record) => $record->status === 'approved')
                    ->columns(2),

                Fieldset::make('Additional Information')
                    ->schema([
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                            TextEntry::make('equipment_list') // Changed from 'equipment' to 'equipment_list'
                            ->label('Equipment')
                            ->state(function (Booking $record): string {
                                if ($record->equipment->isEmpty()) {
                                    return 'No equipment requested';
                                }
                        
                                // Group by equipment name and sum quantities
                                $groupedEquipment = $record->equipment
                                    ->groupBy('name')
                                    ->map(function ($group) {
                                        $totalQuantity = $group->sum('pivot.quantity');
                                        $name = ucwords(str_replace('_', ' ', $group->first()->name));
                                        return "{$name} ({$totalQuantity})";
                                    });
                        
                                return $groupedEquipment->join(', ');
                            })
                            ->icon('heroicon-o-cube'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function formatEquipmentForInfolist($equipment): string
    {
        if ($equipment->isEmpty()) {
            return 'No equipment requested';
        }
    
        // Group by equipment name and sum quantities
        $groupedEquipment = $equipment
            ->groupBy('name')
            ->map(function ($group) {
                $totalQuantity = $group->sum('pivot.quantity');
                $name = ucwords(str_replace('_', ' ', $group->first()->name));
                return "• {$name}: {$totalQuantity} " . ($totalQuantity > 1 ? 'pieces' : 'piece');
            });
    
        return $groupedEquipment->join("\n");
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
                ->multiple(),
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

    private function formatSignatoryStatus($signatories, $role)
    {
        // Check if signatories exist
        if (!$signatories) {
            return 'Pending';
        }
    
        $signatory = $signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'Pending';
        }
    
        return match ($signatory->status) {
            'approved' => 'Approved on ' . Carbon::parse($signatory->approval_date)->setTimezone('Asia/Manila')->format('M d, Y h:i A'),
            'denied' => 'Denied on ' . Carbon::parse($signatory->approval_date)->setTimezone('Asia/Manila')->format('M d, Y h:i A'),
            default => 'Pending',
        };
    }

    protected function getSignatoryIcon($signatories, $role): string
    {
        if (!$signatories) {
            return 'heroicon-o-clock';
        }
    
        $signatory = $signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'heroicon-o-clock';
        }
    
        return match ($signatory->status) {
            'approved' => 'heroicon-o-check-circle',
            'denied' => 'heroicon-o-x-circle',
            default => 'heroicon-o-clock',
        };
    }
    

    protected function getSignatoryColor($signatories, $role): string
    {
        if (!$signatories) {
            return 'warning';
        }
    
        $signatory = $signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'warning';
        }
    
        return match ($signatory->status) {
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
                        ->url(route('filament.user.pages.tracking-page', $booking)),
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
                        ->url(route('filament.user.pages.tracking-page', $booking)),
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
