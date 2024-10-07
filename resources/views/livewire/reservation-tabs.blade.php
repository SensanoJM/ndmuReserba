<div class="flex justify-center space-y-2">
    <x-filament::tabs>
        @foreach($this->getTabs() as $tabId => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $tabId"
                wire:click="setActiveTab('{{ $tabId }}')"
                wire:loading.class="opacity-50 cursor-wait"
                wire:target="setActiveTab('{{ $tabId }}')"
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