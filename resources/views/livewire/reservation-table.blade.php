<div>
    <div wire:loading wire:target="updateActiveTab" class="flex items-center justify-center w-full h-full">
        <x-filament::loading-indicator class="w-8 h-8" />
    </div>
    
    <div wire:loading.remove wire:target="updateActiveTab">
        {{ $this->table }}
    </div>
</div>
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('refresh-page', () => {
            location.reload();
        });
    });
</script>