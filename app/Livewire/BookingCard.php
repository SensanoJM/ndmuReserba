<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Facility;
use Livewire\WithPagination;
use Livewire\Attributes\On; 

class BookingCard extends Component
{
    use WithPagination;

    public $selectedFacility = null;
    public $search = ''; // Search functionality

    #[On('bookingCreated')] 
    public function refreshComponent()
    {
        // This method will be called when the 'bookingCreated' event is dispatched
    }

    public function render()
    {
        $facilities = Facility::where('facility_name', 'like', '%' . $this->search . '%')
            ->orWhere('facility_type', 'like', '%' . $this->search . '%')
            ->orWhere('building_name', 'like', '%' . $this->search . '%')
            ->paginate(4);

        return view('livewire.booking-card', [
            'facilities' => $facilities,
        ]);
    }

    public function selectFacility($facilityId)
    {
        $this->selectedFacility = Facility::find($facilityId);
        $this->dispatch('openBookingModal', facilityId: $facilityId); // Use dispatch instead of emit
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}