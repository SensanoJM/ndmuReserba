<?php

namespace App\Filament\Pages;
use App\Livewire\ReservationTable;

use Filament\Pages\Page;

class ReservationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Reservation';
    protected ?string $heading = '';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $slug = 'Reservation';

    protected static string $view = 'filament.pages.reservation-page';

}
