<?php

namespace App\Filament\Pages;
use App\Livewire\SignatoryTable;

use Filament\Pages\Page;

class SignatoryPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Signatory Dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            // Add any header widgets you want to display
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            // Add any footer widgets you want to display
        ];
    }

    protected static string $view = 'filament.pages.signatory-page';
}
