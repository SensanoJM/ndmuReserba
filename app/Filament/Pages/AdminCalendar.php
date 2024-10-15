<?php

namespace App\Filament\Pages;

use App\Livewire\CalendarWidget;
use App\Models\Facility;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
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
        $this->form->fill();
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
                    ->live(),
                Select::make('user_role')
                    ->label('Filter by User Role')
                    ->options([
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'organization' => 'Organization',
                    ])
                    ->placeholder('All Roles')
                    ->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    

    #[Computed]
    public function calendarWidget(): CalendarWidget
    {
        $widget = new CalendarWidget();

        if ($this->data['facility_id'] ?? null) {
            $widget->modifyQueryUsing(fn($query) => $query->where('facility_id', $this->data['facility_id']));
        }

        if ($this->data['user_role'] ?? null) {
            $widget->modifyQueryUsing(fn($query) => $query->whereHas('user', function ($subQuery) {
                $subQuery->where('role', $this->data['user_role']);
            }));
        }

        return $widget;
    }
}
