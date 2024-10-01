<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Components\Tabs\Tab;
use App\Models\Booking;
use Livewire\Attributes\On;

class ReservationTabs extends Component
{

    public $activeTab = 'all';

    #[On('bookingStatusChanged')]
    public function refreshTabs()
    {
        // This will trigger a re-render of the component
        $this->render();
    }

    /**
     * Get the tabs for the reservation table.
     *
     * @return array
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Reservations')
                ->badge($this->getBookingCount()),
            'pending' => Tab::make('Pending')
                ->badge($this->getBookingCount('pending')),
            'in_review' => Tab::make('In Review')
                ->badge($this->getBookingCount('in_review')),   
            'approved' => Tab::make('Approved')
            ->badge($this->getBookingCount('approved')),   
            'denied' => Tab::make('Denied')
            ->badge($this->getBookingCount('denied')),   
        ];
    }

    private function getBookingCount($status = null)
    {
        $query = Booking::query();
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    public function setActiveTab($tabId)
    {
        $this->activeTab = $tabId;
        $this->dispatch('tabChanged', $tabId);
    }
    
    public function render()
    {
        return view('livewire.reservation-tabs');
    }
}
