<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class BookingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Booking';
    protected ?string $heading = 'Facility Booking';
    protected static ?string $slug = 'booking';

    protected static string $view = 'filament.pages.booking-page';


}
