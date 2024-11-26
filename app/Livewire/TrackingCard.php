<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Filament\Tables\Table;
use IbrahimBougaoua\FilaProgress\Tables\Columns\ProgressBar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TrackingCard extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::where('user_id', Auth::id())
                    ->with([
                        'reservation.signatories',
                        'approvers',
                        'equipment' => function ($query) {
                            $query->select('equipment.*')
                                ->selectRaw('booking_equipment.quantity');
                        },
                    ])
            )
            ->columns([
                TextColumn::make('purpose')
                    ->searchable()
                    ->weight('medium')
                    ->limit(30),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'prebooking' => 'gray',
                        'in_review' => 'warning',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'prebooking' => 'Pre-booking',
                        'in_review' => 'In Review',
                        'pending' => 'Pending Final Approval',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                        default => ucfirst($state),
                    }),
                ProgressBar::make('approval_progress')
                    ->label('Approval Progress')
                    ->getStateUsing(function ($record) {
                        // Total steps (Admin + 4 signatories)
                        $totalSteps = 5;

                        if ($record->status === 'approved') {
                            return [
                                'total' => $totalSteps,
                                'progress' => $totalSteps,
                            ];
                        }

                        if ($record->status === 'denied' || !$record->reservation) {
                            return [
                                'total' => $totalSteps,
                                'progress' => 0,
                            ];
                        }

                        $completedSteps = 0;

                        // Check admin approval
                        if ($record->reservation->admin_approval_date) {
                            $completedSteps++;
                        }

                        // Count approved signatories
                        $completedSteps += $record->reservation->signatories
                            ->where('status', 'approved')
                            ->count();

                        return [
                            'total' => $totalSteps,
                            'progress' => $completedSteps,
                        ];
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->slideOver(true)
                        ->color('success')
                        ->icon('heroicon-s-eye')
                        ->modalContent(function (Booking $record): Infolist {
                            return $this->bookingInfolist($record);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false),
                    Action::make('cancel')
                        ->color('danger')
                        ->icon('heroicon-s-trash')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to cancel this booking? This action cannot be undone.')
                        ->visible(function (Booking $record): bool {
                            return $record->status !== 'approved'
                            && $record->status !== 'denied'
                            && $record->booking_start > now();
                        })
                        ->action(function (Booking $record) {
                            $this->cancelBooking($record);
                        }),
                    Action::make('delete')
                        ->color('danger')
                        ->icon('heroicon-s-archive-box-x-mark')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Approved Booking')
                        ->modalDescription('Are you sure you want to delete this approved booking from your tracking list? This will only remove it from your view and won\'t affect the actual booking.')
                        ->modalSubmitActionLabel('Yes, delete from tracking')
                        ->visible(function (Booking $record): bool {
                            return $record->status === 'approved';
                        })
                        ->action(function (Booking $record) {
                            $this->deleteFromTracking($record);
                        }),
                ]),
                Action::make('pdf')
                    ->label('Download Form')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->visible(fn(Booking $record) => $this->isPdfDownloadable($record))
                    ->action(function (Booking $record) {
                        try {
                            // Ensure all required relationships are loaded
                            $record->load([
                                'reservation.signatories.user',
                                'facility',
                                'user.department',
                                'equipment',
                            ]);

                            // Validate required data
                            if (!$record->reservation || !$record->facility) {
                                throw new \Exception('Required booking information is missing.');
                            }

                            return response()->streamDownload(function () use ($record) {
                                echo Pdf::loadHtml(
                                    Blade::render('bookings.pdf', [
                                        'booking' => $record,
                                        'signatories' => $record->reservation->signatories,
                                        // Add helper function for safe access
                                        'getDepartmentName' => function ($user) {
                                            return $user->department->name ?? 'N/A';
                                        },
                                    ])
                                )->stream();
                            }, "booking-form-{$record->id}.pdf");
                        } catch (\Exception $e) {
                            Log::error('PDF Generation Error', [
                                'booking_id' => $record->id,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Error Generating PDF')
                                ->body('There was an error generating the PDF. Please try again or contact support.')
                                ->danger()
                                ->send();

                            return null;
                        }
                    }),

            ])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateHeading('No bookings')
            ->emptyStateDescription('Once you make a booking, it will appear here.')
            ->poll('10s');
    }

    /**
     * Determine if the booking PDF is downloadable.
     *
     * @param Booking $record
     * @return bool
     */
    protected function isPdfDownloadable(Booking $record): bool
    {
        try {
            if (!$record->reservation || $record->status !== 'approved') {
                return false;
            }

            $allSignatoriesApproved = $record->reservation->signatories()
                ->where('status', '!=', 'approved')
                ->doesntExist();

            // Only send notification if all conditions are met and notification hasn't been sent yet
            if ($allSignatoriesApproved &&
                $record->status === 'approved' &&
                !$record->pdfNotificationSent) {

                // Ensure relationships are loaded
                $record->load(['equipment']);

                Notification::make()
                    ->title('Booking Form Ready')
                    ->body('Your booking has been fully approved. You can now download the booking form.')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('success')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->button()
                            ->label('Download Form')
                            ->url(route('filament.user.pages.tracking-page', $record)),
                    ])
                    ->sendToDatabase($record->user);

                // Mark notification as sent
                $record->update(['pdfNotificationSent' => true]);
            }

            return $allSignatoriesApproved && $record->status === 'approved';
        } catch (\Exception $e) {
            Log::error('PDF Downloadable Check Error', [
                'booking_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function formatEquipmentName(string $name): string
    {
        // Convert snake_case to title case
        return ucwords(str_replace('_', ' ', $name));
    }

    public function bookingInfolist(Booking $record): Infolist
    {
        return Infolist::make()
            ->record($record)
            ->schema([
                Fieldset::make('Approval Progress')
                    ->schema([
                        TextEntry::make('reservation.admin_approval_date')
                            ->label('Pre-booking Approval')
                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->setTimezone('Asia/Manila')->format('M d, Y h:i A') : null)
                            ->icon(fn($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                            ->iconColor(fn($state) => $state ? 'success' : 'warning')
                            ->color(fn($state) => $state ? 'success' : 'warning')
                            ->placeholder('Pending'),
                        TextEntry::make('reservation.signatories')
                            ->label('Adviser Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'adviser'))
                            ->icon(fn($state, $record) => $this->getInfolistSignatoryIcon($record->reservation->signatories, 'adviser'))
                            ->iconColor(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'adviser'))
                            ->color(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'adviser')),
                        TextEntry::make('reservation.signatories')
                            ->label('Dean Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'dean'))
                            ->icon(fn($state, $record) => $this->getInfolistSignatoryIcon($record->reservation->signatories, 'dean'))
                            ->iconColor(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'dean'))
                            ->color(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'dean')),
                        TextEntry::make('reservation.signatories')
                            ->label('School President Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'school_president'))
                            ->icon(fn($state, $record) => $this->getInfolistSignatoryIcon($record->reservation->signatories, 'school_president'))
                            ->iconColor(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'school_president'))
                            ->color(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'school_president')),
                        TextEntry::make('reservation.signatories')
                            ->label('School Director Approval')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'school_director'))
                            ->icon(fn($state, $record) => $this->getInfolistSignatoryIcon($record->reservation->signatories, 'school_director'))
                            ->iconColor(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'school_director'))
                            ->color(fn($state, $record) => $this->getInfolistSignatoryColor($record->reservation->signatories, 'school_director')),
                    ])
                    ->hidden(function (Booking $record) {
                        return $record->status === 'denied';
                    })
                    ->columns(2),

                Fieldset::make('General Information')
                    ->schema([
                        TextEntry::make('facility.facility_name')
                            ->label('Facility')
                            ->icon('heroicon-o-building-office-2'),
                        TextEntry::make('purpose')
                            ->icon('heroicon-o-pencil'),
                        TextEntry::make('contact_number')
                            ->icon('heroicon-o-phone')
                            ->label('Contact Number'),
                        TextEntry::make('status')
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
                            }),
                    ])
                    ->columns(3),

                Fieldset::make('Date & Time')
                    ->schema([
                        TextEntry::make('booking_start')
                            ->label('Start Date')
                            ->formatStateUsing(fn($state) => Carbon::parse($state)->setTimezone('Asia/Manila')->format('M d, Y h:i A'))
                            ->iconColor('primary')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('booking_end')
                            ->label('End Date')
                            ->formatStateUsing(fn($state) => Carbon::parse($state)->setTimezone('Asia/Manila')->format('M d, Y h:i A'))
                            ->iconColor('warning')
                            ->icon('heroicon-o-calendar'),
                    ]),

                Fieldset::make('Additional Details')
                    ->schema([
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                        TextEntry::make('formatted_equipment_list')
                            ->label('Equipment')
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
                return "{$name} ({$totalQuantity})";
            });

        return $groupedEquipment->join(', ');
    }

    protected function getInfolistSignatoryIcon($signatories, $role): string
    {
        if (!$signatories instanceof \Illuminate\Support\Collection) {
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

    protected function getInfolistSignatoryColor($signatories, $role): string
    {
        if (!$signatories instanceof \Illuminate\Support\Collection) {
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

    public function cancelBooking(Booking $booking)
    {
        $user = Auth::user();

        // Check if the booking can be cancelled
        if (($booking->status === 'approved' || $booking->booking_date < now())
            && $booking->status !== 'prebooking') {
            Notification::make()
                ->title('Cannot cancel booking')
                ->body('This booking cannot be cancelled.')
                ->danger()
                ->send();
            return;
        }

        // Delete the booking and related records
        $booking->reservation()->delete();
        $booking->approvers()->delete();
        $booking->equipment()->detach();
        $booking->delete();

        Notification::make()
            ->title('Booking cancelled')
            ->body('Your booking has been successfully cancelled.')
            ->success()
            ->send();

        // Optionally, notify admin about the cancellation
        // You can implement this using your notification system
    }

    protected function canBeDeleted(Booking $record): bool
    {
        return $record->status === 'approved';
    }

    protected function canBeCancelled(Booking $record): bool
    {
        // Only allow cancellation of non-approved bookings that haven't started yet
        return $record->status !== 'approved' &&
        $record->status !== 'denied' &&
        $record->booking_start > now();
    }

    protected function deleteFromTracking(Booking $record): void
    {
        try {
            // Verify the user owns this booking
            if ($record->user_id !== Auth::id()) {
                throw new \Exception('Unauthorized action');
            }

            // Verify the booking can be deleted
            if (!$this->canBeDeleted($record)) {
                throw new \Exception('This booking cannot be deleted from tracking');
            }

            // Soft delete or hide the booking from the user's view
            // Option 1: Use soft deletes
            $record->delete();

            // Option 2: Add a hidden_at timestamp (requires adding this column to bookings table)
            // $record->update(['hidden_at' => now()]);

            Notification::make()
                ->title('Booking Removed')
                ->body('The booking has been removed from your tracking list.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('There was an error removing the booking from tracking.')
                ->danger()
                ->send();

            Log::error('Error deleting booking from tracking', [
                'booking_id' => $record->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function formatSignatoryStatus($signatories, $role)
    {
        if (!$signatories instanceof \Illuminate\Support\Collection) {
            return 'No Signatories';
        }

        $signatory = $signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'No Signatory';
        }

        return match ($signatory->status) {
            'approved' => $signatory->approval_date?->setTimezone('Asia/Manila')->format('M d, Y h:i A'),
            'denied' => $signatory->approval_date?->setTimezone('Asia/Manila')->format('M d, Y h:i A'),
            default => 'Pending',
        };
    }

    public function render()
    {
        return view('livewire.tracking-card');
    }
}
