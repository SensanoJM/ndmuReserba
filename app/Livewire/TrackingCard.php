<?php

namespace App\Livewire;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
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
            ->query(Booking::where('user_id', Auth::id())->with('reservation.signatories', 'approvers', 'equipment'))
            ->columns([
                Split::make([
                    TextColumn::make('purpose')
                        ->searchable()
                        ->weight('medium')
                        ->limit(30),
                    Stack::make([
                        TextColumn::make('PreBooking')
                            ->placeholder('Pre-booking')
                            ->alignCenter(),
                        IconColumn::make('reservation.admin_approval_date')
                            ->icon(fn($record) => $record->reservation?->admin_approval_date ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->color(fn($record) => $record->reservation?->admin_approval_date ? 'primary' : 'danger')
                            ->alignCenter(),
                    ])->space(1),
                    $this->createApprovalColumn('Adviser', 'adviser'),
                    $this->createApprovalColumn('Dean', 'dean'),
                    $this->createApprovalColumn('President', 'school_president'),
                    $this->createApprovalColumn('Director', 'school_director'),
                ])->from('md'),
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
                        ->icon('heroicon-s-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn(Booking $record) => $this->cancelBooking($record)),
                ]),
                    Action::make('pdf')
                    ->label('Download Form')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->visible(fn (Booking $record) => $this->isPdfDownloadable($record))
                    ->action(function (Booking $record) {
                        try {
                            // Ensure all required relationships are loaded
                            $record->load([
                                'reservation.signatories.user',
                                'facility',
                                'user.department',
                                'equipment'
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
                                        'getDepartmentName' => function($user) {
                                            return $user->department->name ?? 'N/A';
                                        }
                                    ])
                                )->stream();
                            }, "booking-form-{$record->id}.pdf");
                        } catch (\Exception $e) {
                            Log::error('PDF Generation Error', [
                                'booking_id' => $record->id,
                                'error' => $e->getMessage()
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
    private function isPdfDownloadable(Booking $record): bool
    {
        try {
            if (!$record->reservation) {
                return false;
            }

            $allApproved = $record->reservation->signatories()
                ->where('status', '!=', 'approved')
                ->doesntExist();

            if ($allApproved && !$record->pdfNotificationSent) {
                // Send notification about PDF availability
                Notification::make()
                    ->title('Booking Form Ready')
                    ->body('All signatories have approved your booking. You can now download the booking form.')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('success')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->button()
                            ->label('Download Form')
                            ->url(route('filament.user.pages.tracking-page', $record))
                    ])
                    ->sendToDatabase($record->user);

                // Mark notification as sent
                $record->update(['pdfNotificationSent' => true]);
            }

            return $allApproved;
        } catch (\Exception $e) {
            Log::error('PDF Downloadable Check Error', [
                'booking_id' => $record->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
                            ->date()
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
                            ->iconColor('primary')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('booking_end')
                            ->label('End Date')
                            ->iconColor('warning')
                            ->icon('heroicon-o-calendar'),
                    ]),

                Fieldset::make('Additional Details')
                    ->schema([
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                        TextEntry::make('equipment')
                            ->label('Equipment')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state, Booking $record) {
                                return $record->equipment->map(function ($equipment) {
                                    return "{$equipment->name}: {$equipment->pivot->quantity}";
                                })->join("\n");
                            })
                            ->placeholder('No equipment requested'),
                    ])
                    ->columns(2),
            ]);
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
        // Check if the booking can be cancelled (e.g., not already approved or in the past)
        if ($booking->status === 'approved' || $booking->booking_date < now()) {
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

    private function createApprovalColumn($label, $role): Stack
    {
        return Stack::make([
            TextColumn::make($role)
                ->placeholder($label)
                ->alignCenter(),
            IconColumn::make('reservation.signatories')
                ->label('')
                ->icon(fn($record) => $this->getSignatoryIcon($record, $role))
                ->color(fn($record) => $this->getSignatoryColor($record, $role))
                ->alignCenter(),
        ]);
    }

    private function getSignatoryIcon($record, $role)
    {
        $signatory = $record->reservation?->signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'heroicon-o-x-circle';
        }
        return match ($signatory->status) {
            'approved' => 'heroicon-o-check-circle',
            'denied' => 'heroicon-o-x-circle',
            default => 'heroicon-o-clock',
        };
    }

    private function getSignatoryColor($record, $role)
    {
        $signatory = $record->reservation?->signatories->firstWhere('role', $role);
        if (!$signatory) {
            return 'danger';
        }
        return match ($signatory->status) {
            'approved' => 'primary',
            'denied' => 'danger',
            default => 'warning',
        };
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
            'approved' => 'Approved on ' . $signatory->approval_date->format('Y-m-d H:i'),
            'denied' => 'Denied on ' . $signatory->approval_date->format('Y-m-d H:i'),
            default => 'Pending',
        };
    }

    public function render()
    {
        return view('livewire.tracking-card');
    }
}
