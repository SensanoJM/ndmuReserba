<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TrackingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Tracking';
    protected ?string $heading = 'Tracking Reservation';
    protected static ?string $slug = 'track-reservation';

    protected static string $view = 'filament.pages.tracking-page';
}
