<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Reservation;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Policies\ReservationPolicy;

class SignatoryTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $recordId;

    public function mount()
    {
        $this->authorize('viewAny', Reservation::class);
    }

    // The getTableQuery method now filters for reservations where all signatories have approved,
    // but the final approval (by the Director) is still pending.
    protected function getTableQuery(): Builder
    {
        return Reservation::query()
            ->whereHas('signatories', function ($query) {
                $query->where('status', 'approved');
            })
            ->whereDoesntHave('signatories', function ($query) {
                $query->where('status', '!=', 'approved');
            })
            ->where('final_approval_date', null)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('booking.user.name')
                ->label('User')
                ->searchable(),
            TextColumn::make('booking.facility.facility_name')
                ->label('Facility')
                ->searchable(),
            TextColumn::make('booking.booking_date')
                ->date()
                ->sortable(),
            TextColumn::make('booking.start_time')
                ->time()
                ->sortable(),
            TextColumn::make('booking.end_time')
                ->time()
                ->sortable(),
            TextColumn::make('booking.purpose')
                ->limit(30),
            BadgeColumn::make('status')
                ->colors([
                    'warning' => 'pending',
                    'success' => 'approved',
                    'danger' => 'denied',
                ])
                ->formatStateUsing(fn($state) => ucfirst($state)),
            TextColumn::make('admin_approval_date')
                ->label('Initial Admin Approval')
                ->date()
                ->sortable(),
        ];
    }

    public function reservationInfolist(Reservation $record): Infolist
    {
        return Infolist::make()
            ->record($record)
            ->schema([
                Tabs::make('Reservation Details')
                    ->tabs([
                        Tabs\Tab::make('Booking Details')
                            ->schema([
                                TextEntry::make('booking.user.name')
                                    ->label('User'),
                                TextEntry::make('booking.facility.facility_name')
                                    ->label('Facility'),
                                TextEntry::make('booking.booking_date')
                                    ->date(),
                                TextEntry::make('booking.start_time')
                                    ->time(),
                                TextEntry::make('booking.end_time')
                                    ->time(),
                                TextEntry::make('booking.purpose'),
                                TextEntry::make('booking.duration'),
                                TextEntry::make('booking.participants'),
                                TextEntry::make('booking.policy'),
                                TextEntry::make('booking.equipment')
                                    ->listWithLineBreaks()
                                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
                                TextEntry::make('booking.status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'info',
                                        'approved' => 'success',
                                        'in_review' => 'warning',
                                        'denied' => 'danger',
                                    }),
                                TextEntry::make('booking.adviser_email')
                                    ->label('Adviser/Faculty/Coach Email'),
                                TextEntry::make('booking.dean_email')
                                    ->label('Dean/Unit Head Email'),
                            ])
                            ->columns(2),
                        Tabs\Tab::make('Signatory Approvals')
                            ->schema([
                                $this->getSignatoryApprovalSchema($record),
                            ]),
                    ]),
            ]);
    }

    private function getSignatoryApprovalSchema(Reservation $reservation): Infolist
    {
        return Infolist::make()
            ->schema($reservation->signatories->map(function ($signatory) {
                return Card::make()
                    ->schema([
                        TextEntry::make("signatories.{$signatory->id}.role")
                            ->label('Role')
                            ->weight('bold'),
                        TextEntry::make("signatories.{$signatory->id}.email")
                            ->label('Email'),
                        IconEntry::make("signatories.{$signatory->id}.status")
                            ->label('Status')
                            ->icon(fn($state) => match ($state) {
                                'approved' => 'heroicon-o-check-circle',
                                'pending' => 'heroicon-o-clock',
                                default => 'heroicon-o-x-circle',
                            })
                            ->color(fn($state) => match ($state) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make("signatories.{$signatory->id}.approval_date")
                            ->label('Approval Date')
                            ->date()
                            ->visible(fn($state) => $state !== null),
                    ])
                    ->columnSpan(1);
            })->toArray());
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->icon('heroicon-o-eye')
                ->modalContent(function (Reservation $record) {
                    return $this->reservationInfolist($record)->render();
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
            Action::make('approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(fn(Reservation $record) => $this->approveReservation($record)),
            Action::make('deny')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->action(fn(Reservation $record) => $this->denyReservation($record)),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            // Add bulk actions if needed
        ];
    }

    public function approveReservation(Reservation $reservation)
    {
        $reservation->update([
            'status' => 'approved',
            'final_approval_date' => now(),
        ]);

        $reservation->booking->update(['status' => 'approved']);

        // Notify the user that their booking has been fully approved
        // You can implement this notification logic here
    }

    public function denyReservation(Reservation $reservation)
    {
        $reservation->update(['status' => 'denied']);
        $reservation->booking->update(['status' => 'denied']);

        // Notify the user that their booking has been denied
        // You can implement this notification logic here
    }

    public function render()
    {
        return view('livewire.signatory-table');
    }
}
