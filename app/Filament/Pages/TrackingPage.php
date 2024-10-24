<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TrackingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Track Bookings';
    protected ?string $heading = 'Tracking Your Bookings';
    protected static ?string $slug = 'track-bookings';

    protected static string $view = 'filament.pages.tracking-page';
}
