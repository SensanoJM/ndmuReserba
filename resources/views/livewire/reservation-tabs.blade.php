<div class="flex justify-center">
    <x-filament::tabs>
        @foreach($this->getTabs() as $tabId => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $tabId"
                wire:click="$set('activeTab', '{{ $tabId }}')"
            >
                {{ $tab->getLabel() }}
                @if ($tab->getBadge())
                    <x-filament::badge>
                        {{ $tab->getBadge() }}
                    </x-filament::badge>
                @endif
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</div>