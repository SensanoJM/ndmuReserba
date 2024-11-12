<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    <div wire:key="calendar-widget-{{ $data['facility_id'] ?? 'all' }}">
        @livewire(\App\Livewire\CalendarWidget::class, [
            'facilityFilter' => $data['facility_id'] ?? null
        ])
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('eventRefresh', () => {
                const calendar = document.querySelector('.filament-fullcalendar');
                if (calendar) {
                    const calendarInstance = calendar.fullCalendar;
                    if (calendarInstance) {
                        calendarInstance.refetchEvents();
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>