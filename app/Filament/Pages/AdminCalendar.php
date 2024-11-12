<?php

namespace App\Filament\Pages;

use App\Livewire\CalendarWidget;
use App\Models\Facility;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;

class AdminCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Calendar';
    protected ?string $heading = 'Active Reservations';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $slug = 'calendar';

    protected static string $view = 'filament.pages.admin-calendar';

    public ?array $data = [];

    public function mount(): void
    {
        // Restore filter from session if it exists
        $savedFilter = Session::get('calendar_facility_filter');
        if ($savedFilter) {
            $this->data['facility_id'] = $savedFilter;
        }
        
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('facility_id')
                    ->label('Filter by Facility')
                    ->options(Facility::pluck('facility_name', 'id'))
                    ->placeholder('All Facilities')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        // Save filter to session
                        Session::put('calendar_facility_filter', $state);
                        $this->dispatch('calendar-filter-changed', facilityId: $state)->to('calendar-widget');
                        $this->redirect(static::getUrl());
                    }),
            ])
            ->statePath('data');
    }

    #[Computed]
    public function calendarWidget(): CalendarWidget
    {
        return new CalendarWidget();
    }
}
