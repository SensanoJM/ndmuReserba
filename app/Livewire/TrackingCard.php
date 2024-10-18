<?php

namespace App\Livewire;

use App\Models\Booking;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Collection;
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
use Livewire\Component;
use Illuminate\Support\Facades\Storage;

class TrackingCard extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::where('user_id', Auth::id())->with('reservation.signatories', 'approvers', 'equipment', 'attachments'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('purpose')
                            ->searchable()
                            ->weight('medium')
                            ->limit(30),
                        TextColumn::make('facility.facility_name')
                            ->searchable()
                            ->color('gray')
                            ->limit(30),
                    ])->space(1),
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
            ])
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateHeading('No bookings')
            ->emptyStateDescription('Once you make a booking, it will appear here.')
            ->poll('10s');
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
                            ->icon('heroicon-o-check-circle')
                            ->iconColor('primary')
                            ->color('primary')
                            ->placeholder('Pending'),
                        TextEntry::make('reservation.signatories')
                            ->label('Adviser Approval')
                            ->icon('heroicon-o-check-circle')
                            ->iconColor('primary')
                            ->color('primary')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'adviser')),
                        TextEntry::make('reservation.signatories')
                            ->label('Dean Approval')
                            ->icon('heroicon-o-check-circle')
                            ->iconColor('primary')
                            ->color('primary')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'dean')),
                        TextEntry::make('reservation.signatories')
                            ->label('School President Approval')
                            ->icon('heroicon-o-check-circle')
                            ->iconColor('primary')
                            ->color('primary')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'school_president')),
                        TextEntry::make('reservation.signatories')
                            ->label('School Director Approval')
                            ->icon('heroicon-o-check-circle')
                            ->iconColor('primary')
                            ->color('primary')
                            ->formatStateUsing(fn($state, $record) => $this->formatSignatoryStatus($record->reservation->signatories, 'school_director')),
                    ])
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
                            ->colors([
                                'danger' => 'denied',
                                'info' => 'pending',
                                'success' => 'approved',
                            ]),
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

                    Fieldset::make('Attachments')
                    ->schema([
                        TextEntry::make('attachments')
                            ->placeholder('No files attached')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state, Booking $record) {
                                if ($record->attachments->isEmpty()) {
                                    return 'No files attached';
                                }
                                return $record->attachments->map(function ($attachment) {
                                    $url = Storage::url($attachment->file_path);
                                    return "<a href='{$url}' target='_blank'>{$attachment->file_name}</a>";
                                })->join("\n");
                            })
                            ->html(),
                    ]),
            ]);
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
        $booking->attachments()->delete();
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
