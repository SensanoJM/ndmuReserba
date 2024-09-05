<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Facility;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use Livewire\WithPagination;


class BookingForm extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $facility_id;
    public $booking_date;
    public $start_time;
    public $end_time;
    public $purpose;
    public $duration;
    public $participants;
    public $policy;
    public $booking_attachments;
    public $equipments = [['item' => '', 'quantity' => 1]];
    public $selectedFacility = null;
    public $search = '';  // Add this property for search functionality

    // Define validation rules for form fields
    protected $rules = [
        'facility_id' => 'required|exists:facilities,id',
        'booking_date' => 'required|date|after:today',
        'start_time' => 'required',
        'end_time' => 'required',
        'purpose' => 'required|max:255',
        'duration' => 'required|max:255',
        'participants' => 'required|max:255',
        'policy' => 'nullable|max:1024',
        'equipments.*.item' => 'nullable|string',
        'equipments.*.quantity' => 'nullable|numeric|min:1',
        'booking_attachments' => 'nullable|file|max:10240',  // 10MB max file size
    ];

    public function addEquipment()
    {
        $this->equipments[] = ['item' => '', 'quantity' => 1];
    }

    public function removeEquipment($index)
    {
        unset($this->equipments[$index]);
        $this->equipments = array_values($this->equipments);
    }

    public function submit()
    {
        $this->validate();

        // Filter out empty equipment entries
        $equipments = array_filter($this->equipments, function($equipment) {
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
            'equipment' => $equipments, // Saving as an array
        ]);

        // Emit event to update the admin's reservation table in real-time
        // $this->emit('bookingCreated');

        // Handle file uploads (if applicable)
        if ($this->booking_attachments) {
            $path = $this->booking_attachments->store('booking_attachments', 'public');
            $booking->update(['booking_attachments' => $path]);
        }

        // Flash success message and reset form
        session()->flash('message', 'Booking created successfully!');
        $this->reset();

        // After successful booking
        $this->selectedFacility = null;
        $this->closeModal();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $facilities = Facility::where('facility_name', 'like', '%' . $this->search . '%')
            ->orWhere('facility_type', 'like', '%' . $this->search . '%')
            ->orWhere('building_name', 'like', '%' . $this->search . '%')
            ->paginate(4);

        return view('livewire.booking-form', [
            'facilities' => $facilities,
        ]);
    }

    public function selectFacility($facilityId)
    {
        $this->selectedFacility = Facility::find($facilityId);
        $this->facility_id = $facilityId;
    }

    public function closeModal()
    {
        $this->selectedFacility = null;
        $this->reset(['facility_id', 'booking_date', 'start_time', 'end_time', 'purpose', 'duration', 'participants', 'policy', 'equipments', 'booking_attachments']);
    }
}
