<?php

namespace App\Livewire;

use App\Events\BookingCreatedEvent;
use App\Http\Requests\BookingFormRequest;
use App\Models\Facility;
use App\Repositories\FacilityRepository;
use App\Services\BookingService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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

    protected $facilityRepository;
    protected $bookingService;

    public function boot(FacilityRepository $facilityRepository, BookingService $bookingService)
    {
        $this->facilityRepository = $facilityRepository;
        $this->bookingService = $bookingService;
    }

    public function mount(): void
    {
        $this->form->fill();
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
                                ->minDate(now()) // Prevent selecting dates before today
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state && Carbon::parse($state)->isPast()) {
                                        $set('booking_start', null);
                                        Notification::make()
                                            ->title('Invalid Date')
                                            ->body('You cannot book a date in the past.')
                                            ->danger()
                                            ->send();
                                    } else {
                                        $this->checkAvailability();
                                    }
                                }),
                            DateTimePicker::make('booking_end')
                                ->label('End Time')
                                ->required()
                                ->minDate(now()) // Prevent selecting dates before today
                                ->reactive()
                                ->after('booking_start')
                                ->afterStateUpdated(fn() => $this->checkAvailability()),
                        ]),
                    TextInput::make('purpose')
                        ->label('Purpose')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('duration')
                        ->label('Indicate Duration of Booking')
                        ->required(),
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
                                ->options($this->equipmentOptions),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(1),
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
                    ->form($this->getFormSchema())
                    ->action(function (array $data, Facility $record): void {
                        $this->data = $data; // Set the form data
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

    public function checkAvailability()
    {
        // Check if booking_start and booking_end are set
        if (!isset($this->data['booking_start']) || !isset($this->data['booking_end'])) {
            $this->isAvailable = true;
            return;
        }

        // Check if booking_start and booking_end are not empty
        if (empty($this->data['booking_start']) || empty($this->data['booking_end'])) {
            $this->isAvailable = true;
            return;
        }

        // Proceed with availability check
        $this->isAvailable = $this->facilityRepository->checkAvailability(
            $this->selectedFacility,
            $this->data['booking_start'],
            $this->data['booking_end']
        );

        $this->notifyAvailability();
    }

    /**
     * Submit the booking form.
     *
     * This method is responsible for creating a new booking based on the validated form data.
     * It will check if the selected time slot is available, and if the booking start date is not before today.
     * If the booking is successful, it will dispatch the "bookingCreated" event.
     *
     * @param BookingFormRequest $request
     * @param Facility $facility
     *
     * @return void
     */
    public function submitBooking(Facility $facility)
    {
        $bookingFormRequest = new BookingFormRequest();
        $validator = Validator::make($this->data, array_merge($bookingFormRequest->rules(), [
            'booking_start' => ['required', 'date', 'after_or_equal:' . now()->toDateTimeString()],
            'booking_end' => ['required', 'date', 'after:booking_start'],
        ]));

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                Notification::make()
                    ->title('Validation Error')
                    ->body($error)
                    ->danger()
                    ->send();
            }
            return;
        }

        $validatedData = $validator->validated();

        // Additional check for past dates
        if (Carbon::parse($validatedData['booking_start'])->isPast()) {
            Notification::make()
                ->title('Invalid Booking Date')
                ->body('You cannot book a date in the past.')
                ->danger()
                ->send();
            return;
        }

        if (!$this->isAvailable) {
            $this->notifyUnavailableTimeSlot();
            return;
        }

        try {
            $booking = $this->bookingService->createBooking($validatedData, $facility, Auth::id());
            event(new BookingCreatedEvent($booking));
            $this->notifyBookingSuccess();
            $this->dispatch('bookingCreated');
        } catch (\Exception $e) {
            $this->notifyBookingError($e);
        }
    }

    protected function notifyInvalidBookingDate()
    {
        Notification::make()
            ->title('Invalid Booking Date')
            ->body('You cannot book a date before today.')
            ->danger()
            ->send();
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

    protected function notifyAvailability()
    {
        $notification = Notification::make()
            ->title($this->isAvailable ? 'Time Slot Available' : 'Time Slot Unavailable')
            ->body($this->isAvailable ? 'The selected time slot is available.' : 'The selected time slot is not available. Please choose a different time.')
            ->icon($this->isAvailable ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->iconColor($this->isAvailable ? 'success' : 'danger');

        $notification->send();
    }

    protected function notifyUnavailableTimeSlot()
    {
        Notification::make()
            ->title('The selected time slot is not available.')
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->send();
    }

    protected function notifyBookingSuccess()
    {
        Notification::make()
            ->title('Booking created successfully!')
            ->icon('heroicon-o-check-circle')
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

        \Illuminate\Support\Facades\Log::error('Booking creation error: ' . $e->getMessage());
    }

    public function render()
    {
        return view('livewire.booking-card');
    }
}
