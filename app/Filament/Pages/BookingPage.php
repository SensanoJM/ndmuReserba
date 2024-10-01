<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class BookingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Booking';
    protected ?string $heading = '';
    protected static ?string $slug = 'Booking';

    protected static string $view = 'filament.pages.booking-page';


}
