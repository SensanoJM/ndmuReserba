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
            ->poll('10s');
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
                ->dateTime(),
            TextColumn::make('booking_end')
                ->dateTime(),
            TextColumn::make('purpose')
                ->limit(30),
            BadgeColumn::make('status')
                ->colors([
                    'info' => 'pending',
                    'warning' => 'in_review',
                    'primary' => 'approved',
                    'danger' => 'denied',
                ])
                ->formatStateUsing(fn($state) => ucfirst($state)),
        ];
    }

    public function bookingInfolist(Booking $booking): Infolist
    {
        return Infolist::make()
            ->record($booking)
            ->schema([
                // ... (other sections remain the same)
                Section::make('Additional Details')
                    ->schema([
                        TextEntry::make('participants')
                            ->icon('heroicon-o-user-group'),
                        TextEntry::make('equipment')
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn ($state, Booking $record) => 
                                $record->equipment->map(fn ($equipment) => 
                                    "{$equipment->name}: {$equipment->pivot->quantity}"
                                )->join(', ')
                            ),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Approval Details')
                    ->schema([
                        TextEntry::make('reservation.signatories')
                            ->label('Approvals')
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn ($state, Booking $record) => 
                                $this->formatSignatoryApprovals($record)
                            ),
                    ])
                    ->collapsible(),
                Section::make('Attachments')
                    ->schema([
                        TextEntry::make('attachments')
                            ->label('Attached Files')
                            ->listWithLineBreaks()
                            ->formatStateUsing(fn ($state, Booking $record) => 
                                $this->formatAttachments($record)
                            )
                            ->html(),
                    ])
                    ->collapsible(),
            ]);
    }

    private function formatAttachments(Booking $booking): string
    {
        return $booking->attachments->map(function ($attachment) {
            $url = \Illuminate\Support\Facades\Storage::url($attachment->file_path);
            return "<a href='{$url}' target='_blank'>{$attachment->file_name}</a>";
        })->join("\n");
    }

    private function formatSignatoryApprovals(Booking $booking): string
    {
        if (!$booking->reservation || $booking->reservation->signatories->isEmpty()) {
            return 'No approvals yet';
        }

        return $booking->reservation->signatories->map(function ($signatory) {
            $userName = $signatory->user->name ?? 'Unknown User';
            $status = ucfirst($signatory->status);
            $approvalDate = $signatory->approval_date
                ? $signatory->approval_date->format('Y-m-d H:i')
                : 'Not approved yet';

            return "{$userName} ({$signatory->role}): {$status} on {$approvalDate}";
        })->join("\n");
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

        Notification::make()
            ->title('Your booking has been denied')
            ->danger()
            ->sendToDatabase($booking->user);

        $this->refreshTable();
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

    #[On('bookingStatusChanged')]
    public function refreshTable()
    {
        // The table will automatically refresh due to Livewire's reactivity
    }

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

        Notification::make()
            ->title('Your booking has been approved')
            ->success()
            ->sendToDatabase($booking->user);

        $this->refreshTable();
    }

    private function createSignatories(Reservation $reservation)
    {
        $booking = $reservation->booking;
        $signatoryRoles = [
            'adviser' => $booking->approvers->firstWhere('role', 'adviser')->email,
            'dean' => $booking->approvers->firstWhere('role', 'dean')->email,
            'school_president' => $this->getSchoolPresidentEmail(),
            'school_director' => $this->getSchoolDirectorEmail(),
        ];

        foreach ($signatoryRoles as $role => $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email for role {$role}: {$email}");
            }

            $user = User::firstWhere('email', $email);

            Signatory::updateOrCreate(
                [
                    'reservation_id' => $reservation->id,
                    'role' => $role,
                ],
                [
                    'email' => $email,
                    'user_id' => $user ? $user->id : null,
                    'status' => 'pending',
                    'approval_token' => Str::random(32),
                ]
            );
        }

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