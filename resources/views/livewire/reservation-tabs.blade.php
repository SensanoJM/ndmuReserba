<div class="flex justify-center space-y-2">
    <x-filament::tabs>
        @foreach($this->getTabs() as $tabId => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $tabId"
                wire:click="setActiveTab('{{ $tabId }}')"
                wire:loading.class="opacity-50 cursor-wait"
                wire:target="setActiveTab('{{ $tabId }}')"
            >
                <div class="flex items-center space-x-2">
                    <span>{{ $tab->getLabel() }}</span>
                    
                    @if ($tab->getBadge())
                        <x-filament::badge>
                            {{ $tab->getBadge() }}
                        </x-filament::badge>
                    @endif
                    
                    <x-filament::loading-indicator
                        class="h-4 w-4"
                        wire:loading
                        wire:target="setActiveTab('{{ $tabId }}')"
                    />
                </div>
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</div>