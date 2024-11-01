<?php

namespace App\Filament\Pages;
use App\Livewire\ReservationTable;

use Filament\Pages\Page;

class ReservationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Reservation';
    protected ?string $heading = 'Manage Reservations';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $slug = 'reservation-page';

    protected static string $view = 'filament.pages.reservation-page';

    /**
     * @return array<string, string>
     */
    /**
     * Filament will call these Livewire component methods when the corresponding
     * events are dispatched. The method name is the key, and the value is the
     * Livewire component method name.
     *
     * @see https://filamentphp.com/docs/2.x/tables/actions#dispatching-events
     */
    public function getListeners()
    {
        return [
            'bookingStatusChanged' => '$refresh',
        ];
    }

}
