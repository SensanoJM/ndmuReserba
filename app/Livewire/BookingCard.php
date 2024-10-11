<?php

namespace App\Livewire;

use App\Models\Facility;
use Filament\Forms\Components\Split;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Livewire\Component;

class BookingCard extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(Facility::query())
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    ImageColumn::make('facility_image')
                        ->height(200)
                        ->extraImgAttributes(['class' => 'object-cover w-full rounded-t-lg']),
                    Stack::make([
                        TextColumn::make('facility_name')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->searchable()
                            ->extraAttributes(['class' => 'text-lg font-bold']),
                            TextColumn::make('facility_type')
                            ->weight(FontWeight::SemiBold)
                            ->prefix('Facility Type: ')
                            ->size('sm')
                            ->icon('heroicon-o-building-office')
                            ->searchable(),
                        TextColumn::make('capacity')
                            ->weight(FontWeight::SemiBold)
                            ->prefix('Capacity: ')
                            ->size('sm')
                            ->icon('heroicon-o-user-group'),
                    ])->space(1)->extraAttributes(['class' => 'p-2 bg-white rounded-b-lg']),
                    Stack::make([
                        TextColumn::make('description')
                            ->size('sm')
                            ->color('gray')
                            ->wrap(),
                            // ->limit(100),
                    ])->space(3)->extraAttributes(['class' => 'p-2 bg-white rounded-b-lg']),
                ])->extraAttributes(['class' => 'bg-white rounded-lg overflow-hidden h-full']),
            ])
            ->filters([
                SelectFilter::make('facility_type')
                    ->label('Facility Type')
                    ->options(fn() => Facility::distinct()->pluck('facility_type', 'facility_type')->toArray())
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->color('primary')
                    ->outlined(true)
                    ->icon('heroicon-o-eye')
                    ->button()
                    ->action(fn(Facility $record) => $this->openFacilityDetails($record->id))
                    ->extraAttributes(['class' => 'w-full justify-center']),
                Action::make('book')
                    ->label('Book Now')
                    ->color('primary')
                    ->icon('heroicon-o-calendar')
                    ->button()
                    ->action(fn(Facility $record) => $this->openBookingModal($record->id))
                    ->extraAttributes(['class' => 'w-full justify-center']),
            ])
            ->filtersFormColumns(3)
            ->paginated([6, 12, 24, 'all'])
            ->deferLoading();
    }

    public function openBookingModal($facilityId)
    {
        $this->dispatch('openBookingModal', facilityId: $facilityId);
    }

     public function openFacilityDetails($facilityId)
     {
        $this->dispatch('openFacilityDetails', facilityId: $facilityId);
    }

    #[On('bookingCreated')]
    public function refreshComponent()
    {
        $this->resetTable();
    }

    public function render()
    {
        return view('livewire.booking-card');
    }
}