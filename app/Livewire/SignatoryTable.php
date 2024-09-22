<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Reservation;
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

class SignatoryTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $recordId;

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
                ->formatStateUsing(fn ($state) => ucfirst($state)),
            TextColumn::make('admin_approval_date')
                ->label('Initial Admin Approval')
                ->date()
                ->sortable(),
        ];
    }

    public function reservationInfolist(Reservation $record): Infolist
    {
        return Infolist::make()
            ->record($record)  // Pass the booking record here
            ->schema([
                Section::make('Booking Details')
                    ->schema([
                        TextEntry::make('bookig.user.name')
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
                    ])
                    ->columns(2),
            ]);
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
