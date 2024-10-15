<?php

namespace App\Livewire;

use App\Models\Approver;
use App\Models\Attachment;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Facility;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class BookingCard extends Component implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;
    use WithFileUploads;

    public $isSlideOverOpen = false;
    public $selectedFacility = null;
    public $availabilityMessage = '';
    public $isAvailable = true;

    public ?array $data = [];

    protected $equipmentOptions = [
        'plastic_chairs' => 'Plastic Chairs',
        'long_table' => 'Long Table',
        'teacher_table' => 'Teacher\'s Table',
        'backdrop' => 'Backdrop',
        'riser' => 'Riser',
        'armed_chair' => 'Armed Chairs',
        'pole' => 'Pole',
        'rostrum' => 'Rostrum',
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Facility::query())
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    ImageColumn::make('facility_image')
                        ->height(200)
                        ->extraImgAttributes(['class' => 'object-cover w-full rounded-t-lg']),
                    Stack::make([
                        TextColumn::make('facility_name')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->searchable()
                            ->extraAttributes(['class' => 'text-lg font-bold']),
                        TextColumn::make('facility_type')
                            ->weight(FontWeight::SemiBold)
                            ->prefix('Facility Type: ')
                            ->size('sm')
                            ->icon('heroicon-o-building-office')
                            ->searchable(),
                        TextColumn::make('capacity')
                            ->weight(FontWeight::SemiBold)
                            ->prefix('Capacity: ')
                            ->size('sm')
                            ->icon('heroicon-o-user-group'),
                    ])->space(1)
                        ->extraAttributes(['class' => 'p-2 bg-white']),
                    Stack::make([
                        TextColumn::make('description')
                            ->size('sm')
                            ->tooltip(fn(Facility $record) => $record->description)
                            ->color('gray')
                            ->limit(30)
                            ->wrap(),
                    ])->space(3)->extraAttributes(['class' => 'p-2 bg-white']),
                ])->extraAttributes(['class' => 'bg-white rounded-lg overflow-hidden h-full relative']),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('View')
                    ->color('primary')
                    ->outlined(true)
                    ->icon('heroicon-o-eye')
                    ->button()
                    ->slideOver()
                    ->infolist(fn(Facility $record): Infolist => $this->facilityInfolist($record))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'w-full justify-center']),
                TableAction::make('book')
                    ->label('Book Now')
                    ->color('primary')
                    ->icon('heroicon-o-calendar')
                    ->button()
                    ->slideOver()
                    ->form($this->getFormSchema())
                    ->action(function (array $data, Facility $record): void {
                        $this->submitBooking($data, $record);
                    })
                    ->extraAttributes(['class' => 'w-full justify-center']),
            ])
            ->filters([
                SelectFilter::make('facility_type')
                    ->label('Facility Type')
                    ->options($this->getCachedFacilityTypes())
                    ->multiple()
                    ->preload(),
            ])
            ->filtersFormColumns(3)
            ->paginated([6, 12, 24, 'all'])
            ->deferLoading();
    }

    protected function getCachedFacilityTypes()
    {
        return Cache::remember('facility_types', now()->addDay(), function () {
            return Facility::distinct()->pluck('facility_type', 'facility_type')->toArray();
        });
    }

    public function facilityInfolist(Facility $facility): Infolist
    {
        return Infolist::make()
            ->record($facility)
            ->schema([
                Split::make([
                    Split::make([
                        Fieldset::make('Basic Information')
                            ->schema([
                                ImageEntry::make('facility_image')
                                    ->label('Facility Image')
                                    ->height(200)
                                    ->extraImgAttributes(['class' => 'object-cover w-full rounded-t-lg']),
                                TextEntry::make('description')
                                    ->label('About this Facility')
                                    ->markdown(),
                            ]),
                    ]),
                ])->from('md'),

                Fieldset::make('Basic Information')
                    ->schema([
                        TextEntry::make('facility_name')
                            ->label('Facility Name')
                            ->icon('heroicon-o-building-office')
                            ->weight(FontWeight::SemiBold),
                        TextEntry::make('facility_type')
                            ->label('Type')
                            ->icon('heroicon-o-tag'),
                        TextEntry::make('capacity')
                            ->label('Capacity')
                            ->icon('heroicon-o-user-group')
                            ->suffix(' people'),
                        TextEntry::make('status')
                            ->label('Availability')
                            ->badge()
                            ->color(fn(string $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn(bool $state): string => $state ? 'Available' : 'Unavailable'),
                    ])
                    ->columns(2),

                Fieldset::make('Location Details')
                    ->schema([
                        TextEntry::make('building_name')
                            ->label('Building')
                            ->icon('heroicon-o-building-office'),
                        TextEntry::make('floor_level')
                            ->label('Floor')
                            ->icon('heroicon-o-arrow-up'),
                        TextEntry::make('room_number')
                            ->label('Room')
                            ->icon('heroicon-o-hashtag'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Booking Details')
                ->description('Please provide the basic details for your booking.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DateTimePicker::make('booking_start')
                                ->label('Start Time')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn() => $this->checkAvailability()),
                            DateTimePicker::make('booking_end')
                                ->label('End Time')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn() => $this->checkAvailability()),
                        ]),
                    TextInput::make('purpose')
                        ->label('Purpose')
                        ->required()
                        ->maxLength(255),
                        TextInput::make('duration')
                        ->label('Duration')
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($this->data['booking_start'] && $this->data['booking_end']) {
                                $start = \Carbon\Carbon::parse($this->data['booking_start']);
                                $end = \Carbon\Carbon::parse($this->data['booking_end']);
                                $duration = $end->diffForHumans($start, ['parts' => 2]);
                                $set('duration', $duration);
                            }
                        }),
                    TextInput::make('participants')
                        ->label('Number of Participants')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('Equipment')
                ->description('Specify any equipment needed for your booking.')
                ->schema([
                    Repeater::make('equipment')
                        ->schema([
                            Select::make('item')
                                ->label('Equipment')
                                ->options($this->equipmentOptions)
                                ->required(),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->addActionLabel('Add Equipment')
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['item'] ?? null),
                ]),

            Section::make('Attachments')
                ->description('Upload any relevant documents for your booking.')
                ->schema([
                    FileUpload::make('attachments')
                        ->label('Booking Attachments')
                        ->directory('booking_attachments')
                        ->maxSize(10240)
                        ->multiple()
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])
                        ->helperText('Max file size: 10MB. Accepted types: PDF, DOC, DOCX, TXT'),
                ]),

            Section::make('Approval Contacts')
                ->description('Provide the email addresses of the contacts to receive information for booking approval.')
                ->schema([
                    Repeater::make('approvers')
                        ->schema([
                            Select::make('role')
                                ->label('Approver Role')
                                ->options([
                                    'adviser' => 'Adviser/Faculty/Coach',
                                    'dean' => 'Dean/Head Unit',
                                ])
                                ->required(),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->maxItems(2)
                        ->addActionLabel('Add Approver')
                        ->itemLabel(fn(array $state): ?string => $state['role'] ?? null),
                ]),
        ];
    }

    public function checkAvailability()
    {
        if (!$this->data['booking_start'] || !$this->data['booking_end']) {
            $this->availabilityMessage = '';
            $this->isAvailable = true;
            return;
        }

        $conflictingBookings = $this->getConflictingBookingsCount();

        if ($conflictingBookings > 0) {
            $this->availabilityMessage = 'The selected time slot is not available.';
            $this->isAvailable = false;
        } else {
            $this->availabilityMessage = 'The selected time slot is available.';
            $this->isAvailable = true;
        }
    }

    protected function getConflictingBookingsCount()
    {
        return Booking::where('facility_id', $this->selectedFacility->id)
            ->where(function ($query) {
                $query->whereBetween('booking_start', [$this->data['booking_start'], $this->data['booking_end']])
                    ->orWhereBetween('booking_end', [$this->data['booking_start'], $this->data['booking_end']])
                    ->orWhere(function ($query) {
                        $query->where('booking_start', '<=', $this->data['booking_start'])
                            ->where('booking_end', '>=', $this->data['booking_end']);
                    });
            })
            ->count();
    }

    public function submitBooking(array $data, Facility $facility)
    {
        if (!$this->isAvailable) {
            $this->notifyUnavailableTimeSlot();
            return;
        }

        DB::beginTransaction();

        try {
            $booking = $this->createBooking($data, $facility);
            $this->createEquipmentEntries($booking, $data['equipment']);
            $this->createApprovers($booking, $data);
            $this->handleAttachments($booking, $data['attachments'] ?? []);

            DB::commit();

            $this->notifyBookingSuccess();
            $this->dispatch('bookingCreated');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notifyBookingError($e);
        }
    }

    protected function createBooking(array $data, Facility $facility): Booking
    {
        return Booking::create([
            'facility_id' => $facility->id,
            'booking_start' => $data['booking_start'],
            'booking_end' => $data['booking_end'],
            'purpose' => $data['purpose'],
            'duration' => $data['duration'],
            'participants' => $data['participants'],
            'policy' => $data['policy'],
            'user_id' => Auth::id(),
        ]);
    }

    protected function createEquipmentEntries(Booking $booking, array $equipmentData)
    {
        foreach ($equipmentData as $item) {
            $booking->equipment()->create([
                'name' => $item['item'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    protected function createApprovers(Booking $booking, array $data)
    {
        foreach ($data['approvers'] as $approverData) {
            Approver::create([
                'booking_id' => $booking->id,
                'email' => $approverData['email'],
                'role' => $approverData['role'],
            ]);
        }
    }

    protected function handleAttachments(Booking $booking, array $attachments)
    {
        foreach ($attachments as $file) {
            $path = $file->store('booking_attachments', 'public');
            Attachment::create([
                'booking_id' => $booking->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'upload_date' => now(),
            ]);
        }
    }

    protected function notifyUnavailableTimeSlot()
    {
        Notification::make()
            ->title('The selected time slot is not available.')
            ->danger()
            ->send();
    }

    protected function notifyBookingSuccess()
    {
        Notification::make()
            ->title('Booking created successfully!')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->body('Please wait for Signatory approval. You can Track your booking anytime.')
            ->send();
    }

    protected function notifyBookingError(\Exception $e)
    {
        Notification::make()
            ->title('Error creating booking')
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->body('An error occurred while creating your booking. Please try again later.')
            ->send();

        // Log the error for debugging
        \Illuminate\Support\Facades\Log::error('Booking creation error: ' . $e->getMessage());
    }

    public function openFacilityDetails($facilityId)
    {
        $this->dispatch('openFacilityDetails', facilityId: $facilityId);
    }

    #[On('bookingCreated')]
    public function refreshComponent()
    {
        $this->resetTable();
    }

    public function render()
    {
        return view('livewire.booking-card');
    }
}
