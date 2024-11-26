<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TrackingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Track Bookings';
    protected ?string $heading = 'Booking Status';
    protected static ?string $slug = 'tracking-page';

    protected static string $view = 'filament.pages.tracking-page';
}
