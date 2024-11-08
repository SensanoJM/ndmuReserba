<?php

namespace App\Livewire;

use App\Events\BookingCreatedEvent;
use App\Http\Requests\BookingFormRequest;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Facility;
use App\Repositories\FacilityRepository;
use App\Services\BookingService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class BookingCard extends Component implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    public $isSlideOverOpen = false;
    public $selectedFacility = null;
    public $isAvailable = true;
    
    protected array $validatedData = [];
    protected $facilityRepository;
    protected $bookingService;

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

    public function boot(FacilityRepository $facilityRepository, BookingService $bookingService)
    {
        $this->facilityRepository = $facilityRepository;
        $this->bookingService = $bookingService;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(Facility $record = null): array
    {
        return [
            Section::make('Booking Details')
                ->description('Please provide the basic details for your booking.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Hidden::make('facility_id')
                                ->default(fn() => $record?->id)
                                ->required(),
                            TextInput::make('facility_name')
                                ->label('Facility')
                                ->default(fn() => $record?->facility_name)
                                ->disabled()
                                ->columnSpanFull()
                                ->hidden(fn() => !$record),
                            DateTimePicker::make('booking_start')
                                ->label('Start Time')
                                ->required()
                                ->minDate(now())
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if ($state && Carbon::parse($state)->isPast()) {
                                        $set('booking_start', null);
                                        Notification::make()
                                            ->title('Invalid Date')
                                            ->body('You cannot book a date in the past.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $this->checkAvailability();
                                    $this->updateDuration($get, $set);
                                }),
                            DateTimePicker::make('booking_end')
                                ->label('End Time')
                                ->required()
                                ->minDate(now())
                                ->reactive()
                                ->after('booking_start')
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $this->checkAvailability();
                                    $this->updateDuration($get, $set);
                                }),
                        ]),
                    TextInput::make('purpose')
                        ->label('Purpose')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('duration')
                        ->label('Duration')
                        ->disabled()
                        ->dehydrated(true) // Ensure the value is included when form is submitted
                        ->hint('Automatically calculated from start and end times'),
                    TextInput::make('participants')
                        ->label('Number of Participants')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

                Section::make('Equipment')
                ->description('Specify any equipment needed for your booking. Leave empty if no equipment is needed.')
                ->schema([
                    Repeater::make('equipment')
                        ->schema([
                            Select::make('item')
                                ->label('Equipment')
                                ->options($this->equipmentOptions)
                                ->nullable(),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(1)
                                ->nullable()
                                ->required(fn (Get $get): bool => filled($get('item'))),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Add Equipment')
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn(array $state): ?string => $state['item'] ?? 'No equipment selected')
                        ->deletable(true)
                        ->reorderable(true)
                        ->nullable(),
                ]),

            Section::make('Approval Contacts')
                ->description('Provide the email addresses of the contacts to receive information for booking approval.')
                ->schema([
                    TextInput::make('adviser_email')
                        ->label('Adviser/Faculty/Coach Email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('dean_email')
                        ->label('Dean/Head Unit Email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]),
        ];
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
                        ->height('100%')
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-lg']),
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
                    ->form(fn(Facility $record) => $this->getFormSchema($record))
                    ->action(function (array $data, Facility $record): void {
                        $this->data = $data;
                        $this->submitBooking($record);
                    })
                    ->extraAttributes(['class' => 'w-full justify-center']),
            ])
            ->filters([
                SelectFilter::make('facility_type')
                    ->label('Facility Type')
                    ->options(fn() => $this->facilityRepository->getCachedFacilityTypes())
                    ->multiple()
                    ->preload(),
            ])
            ->filtersFormColumns(3)
            ->paginated([6, 12, 24, 'all'])
            ->deferLoading(true);
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

    protected function updateDuration(Get $get, Set $set): void
    {
        $start = $get('booking_start');
        $end = $get('booking_end');

        if ($start && $end) {
            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            if ($endTime->lte($startTime)) {
                $set('booking_end', null);
                Notification::make()
                    ->title('Invalid Time')
                    ->body('End time must be after start time.')
                    ->danger()
                    ->send();
                return;
            }

            // Calculate the duration
            $duration = $this->calculateDuration($startTime, $endTime);
            $set('duration', $duration);
        }
    }

    protected function calculateDuration(Carbon $start, Carbon $end): string
    {
        $totalMinutes = $start->diffInMinutes($end);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $durationParts = [];

        if ($hours > 0) {
            $durationParts[] = "{$hours} " . ($hours === 1 ? 'hour' : 'hours');
        }

        if ($minutes > 0) {
            $durationParts[] = "{$minutes} " . ($minutes === 1 ? 'minute' : 'minutes');
        }

        return implode(' ', $durationParts);
    }

    public function checkAvailability()
    {
        if (empty($this->data['booking_start']) ||
            empty($this->data['booking_end']) ||
            empty($this->data['facility_id'])) {
            $this->isAvailable = true;
            return;
        }

        $conflictingBookings = Booking::where('facility_id', $this->data['facility_id'])
            ->where(function ($query) {
                $query->whereBetween('booking_start', [$this->data['booking_start'], $this->data['booking_end']])
                    ->orWhereBetween('booking_end', [$this->data['booking_start'], $this->data['booking_end']])
                    ->orWhere(function ($query) {
                        $query->where('booking_start', '<=', $this->data['booking_start'])
                            ->where('booking_end', '>=', $this->data['booking_end']);
                    });
            })
            ->count();

        $this->isAvailable = ($conflictingBookings === 0);

        if ($this->isAvailable) {
            $this->notifySuccess('Time Slot Available', 'The selected time slot is available.');
        } else {
            $this->notifyError('Time Slot Unavailable', 'The selected time slot is not available. Please choose a different time.');
        }
    }

    public function submitBooking(Facility $facility)
    {
        if (!$this->validateBooking()) {
            return;
        }

        if (!$this->checkBookingConstraints()) {
            return;
        }

        try {
            $booking = $this->bookingService->createBooking(
                $this->validatedData,
                $facility,
                Auth::id()
            );
            
            $this->notifySuccess(
                'Booking Created',
                'Your booking has been created and is pending approval. You can track its status in the tracking page.'
            );
            
            event(new BookingCreatedEvent($booking));
            $this->dispatch('bookingCreated');
            
        } catch (\Exception $e) {
            $this->notifyError(
                'Booking Creation Failed',
                'There was an error creating your booking. Please try again later.'
            );
            
            Log::error('Booking creation error:', [
                'error' => $e->getMessage(),
                'data' => $this->validatedData
            ]);
        }
    }

    protected function validateBooking(): bool
    {
        $validator = Validator::make(
            $this->data,
            $this->getValidationRules()
        );

        if ($validator->fails()) {
            $this->showValidationErrors($validator->errors()->all());
            return false;
        }

        $this->validatedData = $validator->validated();
        return true;
    }

    protected function getValidationRules(): array
    {
        return array_merge(
            (new BookingFormRequest())->rules(),
            [
                'booking_start' => ['required', 'date', 'after_or_equal:' . now()->toDateTimeString()],
                'booking_end' => ['required', 'date', 'after:booking_start'],
                'equipment' => ['nullable', 'array'],
                'equipment.*.item' => ['nullable', 'string'],
                'equipment.*.quantity' => ['nullable', 'integer', 'min:1', 'required_with:equipment.*.item'],
                'adviser_email' => ['required', 'email'],
                'dean_email' => ['required', 'email'],
            ]
        );
    }

    protected function checkBookingConstraints(): bool
    {
        if (Carbon::parse($this->validatedData['booking_start'])->isPast()) {
            $this->notifyError('Invalid Booking Date', 'You cannot book a date in the past.');
            return false;
        }

        if (!$this->isAvailable) {
            $this->notifyError('Time Slot Unavailable', 'The selected time slot is not available. Please choose a different time.');
            return false;
        }

        return true;
    }

    /**
     * Create a new booking record in the database.
     *
     * @param  \App\Models\Facility  $facility
     * @return \App\Models\Booking
     */
    protected function createBookingRecord(Facility $facility): Booking
    {
        return $this->bookingService->createBooking(
            $this->validatedData,
            $facility,
            Auth::id()
        );
    }

    protected function showValidationErrors(array $errors): void
    {
        foreach ($errors as $error) {
            Notification::make()
                ->title('Validation Error')
                ->body($error)
                ->danger()
                ->send();
        }
    }

    protected function notifySuccess(string $title, string $message = null): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->icon('heroicon-o-check-circle')
            ->body($message)
            ->duration(5000)
            ->send();
    }

    /**
     * Display an error notification with custom title and message
     */
    protected function notifyError(string $title, string $message = null): void
    {
        Notification::make()
            ->title($title)
            ->danger()
            ->icon('heroicon-o-x-circle')
            ->body($message)
            ->duration(5000)
            ->send();
    }

    protected function handleBookingError(\Exception $e): void
    {
        $this->notifyError(
            'Booking Creation Failed',
            'There was an error creating your booking. Please try again later.'
        );

        if (app()->environment('local')) {
            $this->notifyError('Error Details', $e->getMessage());
        }

        Log::error('Booking creation error: ' . $e->getMessage());
    }

    protected function handleValidationErrors($exception)
    {
        foreach ($exception->errors() as $field => $errors) {
            foreach ($errors as $error) {
                Notification::make()
                    ->title('Validation Error')
                    ->body($error)
                    ->danger()
                    ->send();
            }
        }
    }

    public function render()
    {
        return view('livewire.booking-card');
    }
}
