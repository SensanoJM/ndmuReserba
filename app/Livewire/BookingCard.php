<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Facility;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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
use Filament\Tables\Actions\Modal\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
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
                    // Image and main content
                    ImageColumn::make('facility_image')
                        ->height(200)
                        ->extraImgAttributes(['class' => 'object-cover w-full rounded-t-lg']),

                    // Main Content
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
                        ->extraAttributes(['class' => 'p-2 bg-white']), // Content area without flex-grow

                    // Description content
                    Stack::make([
                        TextColumn::make('description')
                            ->size('sm')
                            ->tooltip(fn(Facility $record) => $record->description)
                            ->color('gray')
                            ->limit(30)
                            ->wrap(),
                    ])->space(3)->extraAttributes(['class' => 'p-2 bg-white']),
                ])->extraAttributes(['class' => 'bg-white rounded-lg overflow-hidden h-full relative']), // Main card is relatively positioned
            ])
            ->actions([ // Move actions to the 'actions' method
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
                    ->options(fn() => Facility::distinct()->pluck('facility_type', 'facility_type')->toArray())
                    ->multiple()
                    ->preload(),
            ])
            ->filtersFormColumns(3)
            ->paginated([6, 12, 24, 'all'])
            ->deferLoading();
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
                    DatePicker::make('booking_date')
                        ->label('Booking Date')
                        ->minDate(now()->addDay())
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn() => $this->checkAvailability()),
                    Grid::make(2)
                        ->schema([
                            TimePicker::make('start_time')
                                ->label('Start Time')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn() => $this->checkAvailability()),
                            TimePicker::make('end_time')
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
                        ->required()
                        ->maxLength(255),
                    TextInput::make('participants')
                        ->label('Number of Participants')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('Equipment and Policy')
                ->description('Specify any equipment needed and review the policy.')
                ->schema([
                    Repeater::make('equipments')
                        ->schema([
                            Select::make('item')
                                ->label('Item')
                                ->options([
                                    'plastic_chairs' => 'Plastic Chairs',
                                    'long_table' => 'Long Table',
                                    'teacher_table' => 'Teacher\'s Table',
                                    'backdrop' => 'Backdrop',
                                    'riser' => 'Riser',
                                    'armed_chair' => 'Armed Chairs',
                                    'pole' => 'Pole',
                                    'rostrum' => 'Rostrum',
                                ]),
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
                    Textarea::make('policy')
                        ->label('Policy')
                        ->maxLength(1024)
                        ->hint('Please review and accept the booking policy.'),
                ]),

            Section::make('Attachments')
                ->description('Upload any relevant documents for your booking.')
                ->schema([
                    FileUpload::make('booking_attachments')
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

    public function checkAvailability()
    {
        if (!$this->data['booking_date'] || !$this->data['start_time'] || !$this->data['end_time']) {
            $this->availabilityMessage = '';
            $this->isAvailable = true;
            return;
        }

        $conflictingBookings = Booking::where('facility_id', $this->selectedFacility->id)
            ->where('booking_date', $this->data['booking_date'])
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->data['start_time'], $this->data['end_time']])
                    ->orWhereBetween('end_time', [$this->data['start_time'], $this->data['end_time']])
                    ->orWhere(function ($query) {
                        $query->where('start_time', '<=', $this->data['start_time'])
                            ->where('end_time', '>=', $this->data['end_time']);
                    });
            })
            ->count();

        if ($conflictingBookings > 0) {
            $this->availabilityMessage = 'The selected time slot is not available.';
            $this->isAvailable = false;
        } else {
            $this->availabilityMessage = 'The selected time slot is available.';
            $this->isAvailable = true;
        }
    }

    public function submitBooking(array $data, Facility $facility)
    {
        if (!$this->isAvailable) {
            Notification::make()
                ->title('The selected time slot is not available.')
                ->danger()
                ->send();
            return;
        }

        $booking = Booking::create([
            'facility_id' => $facility->id,
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'],
            'duration' => $data['duration'],
            'participants' => $data['participants'],
            'policy' => $data['policy'],
            'user_id' => Auth::id(),
            'equipment' => $data['equipments'],
            'adviser_email' => $data['adviser_email'],
            'dean_email' => $data['dean_email'],
        ]);

        if (!empty($data['booking_attachments'])) {
            $paths = collect($data['booking_attachments'])->map(function ($file) {
                return $file->store('booking_attachments', 'public');
            })->toArray();

            $booking->update(['booking_attachments' => json_encode($paths)]);
        }

        $this->dispatch('bookingCreated');

        Notification::make()
            ->title('Booking created successfully!')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->body('Please wait for Signatory approval. You can Track your booking anytime.')
            ->send();
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
