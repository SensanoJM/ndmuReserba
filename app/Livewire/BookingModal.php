<?php
namespace App\Livewire;

use App\Models\Booking;
use App\Models\Facility;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class BookingModal extends Component implements HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use WithFileUploads;

    public $isOpen = false;
    public $facility_id;
    public $booking_date;
    public $start_time;
    public $end_time;
    public $purpose;
    public $duration;
    public $participants;
    public $policy;
    public $booking_attachments = [];
    public $equipments = [];
    public $selectedFacility = null;
    public $availabilityMessage = '';
    public $isAvailable = true;

    public function render()
    {
        return view('livewire.booking-modal');
    }

    #[On('openBookingModal')]
    public function openModal($facilityId)
    {
        $this->isOpen = true;
        $this->facility_id = $facilityId;
        $this->selectedFacility = Facility::find($facilityId);
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->reset([
            'facility_id', 'booking_date', 'start_time', 'end_time', 'purpose',
            'duration', 'participants', 'policy', 'equipments', 'booking_attachments',
            'availabilityMessage', 'isAvailable',
        ]);
    }

    protected function rules()
    {
        return [
            // ... other rules ...
            'booking_attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,txt',
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                Step::make('Date and Time')
                    ->schema([
                        DatePicker::make('booking_date')
                            ->label('Booking Date')
                            ->minDate(now()->addDay())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn() => $this->checkAvailability()),
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
                Step::make('Booking Details')
                    ->schema([
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
                        Textarea::make('policy')
                            ->label('Policy')
                            ->maxLength(1024),
                    ]),
                Step::make('Equipments')
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
                            ->defaultItems(1)
                            ->addActionLabel('Add Equipment')
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['item'] ?? null),
                    ]),
                Step::make('Attachments')
                    ->schema([
                        FileUpload::make('booking_attachments')
                            ->label('Booking Attachments')
                            ->directory('booking_attachments')
                            ->maxSize(10240)
                            ->multiple() // Add this if you want to allow multiple file uploads
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']), // Specify accepted file types
                    ]),
            ])
                ->submitAction(Action::make('submit')
                        ->label('Submit')
                        ->color('success')
                        ->action('submit'))
                ->cancelAction(Action::make('cancel')
                        ->label('Cancel')
                        ->color('danger')
                        ->action('closeModal')),
        ];
    }

    public function checkAvailability()
    {
        if (!$this->booking_date || !$this->start_time || !$this->end_time) {
            $this->availabilityMessage = '';
            $this->isAvailable = true;
            return;
        }

        $conflictingBookings = Booking::where('facility_id', $this->facility_id)
            ->where('booking_date', $this->booking_date)
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                    ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                    ->orWhere(function ($query) {
                        $query->where('start_time', '<=', $this->start_time)
                            ->where('end_time', '>=', $this->end_time);
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

    public function submit()
    {
        Log::info('Booking attachments:', ['attachments' => $this->booking_attachments]);

        $this->validate();

        if (!$this->isAvailable) {
            $this->addError('booking_date', 'The selected time slot is not available.');
            return;
        }

        // Validate the form data
        $this->validate([
            'equipments' => ['array'],
            'equipments.*.item' => ['required', 'string'],
            'equipments.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Filter out empty equipment entries (this step might be unnecessary with the validation above)
        $equipments = array_filter($this->equipments, function ($equipment) {
            return !empty($equipment['item']) && !empty($equipment['quantity']);
        });

        // Create a new booking using the form data
        $booking = Booking::create([
            'facility_id' => $this->facility_id,
            'booking_date' => $this->booking_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'purpose' => $this->purpose,
            'duration' => $this->duration,
            'participants' => $this->participants,
            'policy' => $this->policy,
            'user_id' => Auth::id(),
            'equipment' => $equipments,
        ]);

        // Handle file uploads
        if (!empty($this->booking_attachments)) {
            $paths = collect($this->booking_attachments)->map(function ($file) {
                return $file->store('booking_attachments', 'public');
            })->toArray();

            $booking->update(['booking_attachments' => json_encode($paths)]);
        }

        $this->dispatch('bookingCreated');

        $this->closeModal();

        // Display a notification
        Notification::make()
            ->title('Booking created successfully!')
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->send();
    }
}
