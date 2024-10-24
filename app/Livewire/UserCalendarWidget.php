<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;

class UserCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Booking::class;

    public $isAvailable = true;

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

    public function fetchEvents(array $fetchInfo): array
    {
        return Booking::query()
            ->with('user', 'facility')
            ->where('status', 'approved')
            ->whereBetween('booking_start', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->purpose,
                    'start' => $booking->booking_start,
                    'end' => $booking->booking_end,
                    'allDay' => false,
                    'backgroundColor' => $this->getEventColor($booking->user->role),
                    'extendedProps' => [
                        'user' => $booking->user->name,
                        'facility' => $booking->facility->facility_name,
                    ],
                ];
            })
            ->toArray();
    }

    public function getFormSchema(): array
    {
        return [
            Hidden::make('user_id')
            ->default(Auth::id()),  // Set the default value to the current user's ID

            Section::make('Booking Details')
                ->description('Please provide the basic details for your booking.')
                ->schema([
                    Select::make('facility_id')
                        ->label('Facility')
                        ->options(Facility::query()->pluck('facility_name', 'id'))
                        ->searchable()
                        ->required()
                        ->preload(),
                    Grid::make(2)
                        ->schema([
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

    protected function notifyAvailability()
    {
        $notification = Notification::make()
            ->title($this->isAvailable ? 'Time Slot Available' : 'Time Slot Unavailable')
            ->body($this->isAvailable ? 'The selected time slot is available.' : 'The selected time slot is not available. Please choose a different time.')
            ->icon($this->isAvailable ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->iconColor($this->isAvailable ? 'success' : 'danger');

        $notification->send();
    }

    public function getEventColor(string $userRole): string
    {
        return match ($userRole) {
            'organization' => '#4CAF50',
            'faculty' => '#2196F3',
            'admin' => '#FF5722',
            default => '#9C27B0',
        };
    }

    public function config(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'initialView' => 'dayGridMonth',
            'editable' => false,
            'selectable' => true,
            'dayMaxEvents' => true,
            'eventDurationEditable' => false,
            'selectConstraint' => [
                'start' => now()->startOfDay()->format('Y-m-d'), // Start from today
            ],
            'validRange' => [
                'start' => now()->startOfDay()->format('Y-m-d'), // Disable all dates before today
            ],
            'selectOverlap' => false, // Prevent selection of overlapping events
        ];
    }

    protected function createAction(): ?array
    {
        return [
            'action' => function (array $data) {
                try {
                    DB::beginTransaction();

                    // Create the booking with the data from the form
                    $booking = Booking::create($data);  // The user_id will now be included in $data

                    // Handle equipment if present
                    if (!empty($data['equipment'])) {
                        foreach ($data['equipment'] as $item) {
                            if (!empty($item['item']) && !empty($item['quantity'])) {
                                $equipment = Equipment::firstOrCreate(['name' => $item['item']]);
                                $booking->equipment()->attach($equipment->id, ['quantity' => $item['quantity']]);
                            }
                        }
                    }

                    // Handle attachments if present
                    if (!empty($data['attachments'])) {
                        foreach ($data['attachments'] as $file) {
                            $path = $file->store('booking_attachments', 'public');
                            $booking->attachments()->create([
                                'file_name' => $file->getClientOriginalName(),
                                'file_path' => $path,
                                'file_type' => $file->getClientMimeType(),
                                'upload_date' => now(),
                            ]);
                        }
                    }

                    // Create approvers
                    $booking->approvers()->createMany([
                        [
                            'email' => $data['adviser_email'],
                            'role' => 'adviser',
                        ],
                        [
                            'email' => $data['dean_email'],
                            'role' => 'dean',
                        ],
                    ]);

                    DB::commit();

                    Notification::make()
                        ->title('Booking created successfully')
                        ->success()
                        ->send();

                    $this->refreshEvents();

                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    Notification::make()
                        ->title('Error creating booking')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            },
            'modal' => [
                'title' => 'Create Booking',
            ],
        ];
    }

    private function refreshEvents(): void
    {
        $this->dispatch('eventRefresh');
    }
}