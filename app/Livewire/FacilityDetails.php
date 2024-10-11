<?php

namespace App\Livewire;

use App\Models\Facility;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Livewire\Component;
use Livewire\Attributes\On;

class FacilityDetails extends Component implements HasInfolists
{
    use InteractsWithInfolists;

    public $facilityId;
    public $isOpen = false;

    #[On('openFacilityDetails')]
    public function openModal($facilityId)
    {
        $this->facilityId = $facilityId;
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function facilityInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(Facility::findOrFail($this->facilityId))
            ->columns(1)
            ->schema([
                Section::make('Facility Information')
                    ->schema([
                        ImageEntry::make('facility_image')
                            ->label('Facility Image')
                            ->height(200)
                            ->extraImgAttributes(['class' => 'object-cover w-full rounded-t-lg']),
                        TextEntry::make('facility_name')
                            ->label('Facility Name')
                            ->icon('heroicon-o-building-office-2')
                            ->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('facility_type')
                            ->label('Type')
                            ->icon('heroicon-o-tag'),
                        TextEntry::make('capacity')
                            ->label('Capacity')
                            ->icon('heroicon-o-user-group')
                            ->suffix(' people'),
                    ])
                    ->columns(2),
                
                Section::make('Location')
                    ->schema([
                        TextEntry::make('building_name')
                            ->label('Building')
                            ->icon('heroicon-o-map-pin'),
                        TextEntry::make('floor_level')
                            ->label('Floor')
                            ->icon('heroicon-o-arrow-up')
                            ->suffix(fn (Facility $record): string => $record->floor_level === 1 ? 'st floor' : 'th floor'),
                        TextEntry::make('room_number')
                            ->label('Room')
                            ->icon('heroicon-o-hashtag'),
                    ])
                    ->columns(3),
                
                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->label('About this Facility')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Status')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Availability')
                            ->badge()
                            ->color(fn (string $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Available' : 'Unavailable'),
                    ]),
            ]);
    }

    public function render()
    {
        $facilityInfolist = $this->isOpen ? $this->facilityInfolist(Infolist::make()) : null;
        
        return view('livewire.facility-details', [
            'facilityInfolist' => $facilityInfolist,
        ]);
    }
}