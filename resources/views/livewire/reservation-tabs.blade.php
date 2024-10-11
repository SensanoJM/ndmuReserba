@php
    use Illuminate\Support\HtmlString;
    use Illuminate\Support\Facades\Blade;
@endphp

<div class="flex justify-center space-y-2">
    <x-filament::tabs>
        @foreach($this->getTabs() as $tabId => $tab)
            <x-filament::tabs.item
                :active="$activeTab === $tabId"
                wire:click="setActiveTab('{{ $tabId }}')"
                wire:loading.class="opacity-50 cursor-wait"
                wire:target="setActiveTab('{{ $tabId }}')"
            >
                <div class="flex items-center">
                    {{ $tab->getLabel() }}
                    @if ($tab->getBadge())
                        <x-filament::badge>
                            {{ $tab->getBadge() }}
                        </x-filament::badge>
                    @endif
                    {!! new HtmlString(Blade::render("
                        <x-filament::loading-indicator
                            class=\"ml-2 h-4 w-4\"
                            wire:loading
                            wire:target=\"setActiveTab('{$tabId}')\"
                        />
                    ")) !!}
                </div>
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</div>