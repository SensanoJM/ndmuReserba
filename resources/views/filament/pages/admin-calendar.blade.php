<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    <x-filament::section>
        @livewire(\App\Livewire\CalendarWidget::class)
    </x-filament::section>
</x-filament-panels::page>