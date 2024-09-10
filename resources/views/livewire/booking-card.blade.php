<div>
    @if (session()->has('message'))
        <div class="relative px-4 py-3 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Grid Layout using Filament Grid Component -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-1">
        <!-- Search Bar -->
        <div class="mb-6 flex justify-end">
            <input type="text" wire:model.live="search" placeholder="Search facilities..."
                class="w-48 p-2 text-black border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        @forelse($facilities as $facility)
            <x-filament::card>
                <div
                    class="flex flex-col sm:flex-row items-start sm:items-center gap-4 space-y-4 sm:space-y-0 sm:space-x-4">
                    <!-- Facility Image Container -->
                    <div class="shrink-0 w-32 h-32 relative bg-gray-100 rounded-lg overflow-hidden">
                        @if ($facility->facility_image)
                            <div class="absolute inset-0 flex items-center justify-center">
                                <img src="{{ Storage::url($facility->facility_image) }}"
                                    alt="{{ $facility->facility_name }}" class="w-full h-full object-contain" />
                            </div>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-gray-500 text-sm">No image</span>
                            </div>
                        @endif
                    </div>

                    <!-- First Column Facility Details -->
                    <div class="flex-grow space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            <h3 class="text-lg font-medium">{{ $facility->facility_name }}</h3>
                            <div>
                                <span class="font-medium">Capacity:</span> {{ $facility->capacity }}
                            </div>
                            <div>
                                <span class="font-medium">Facility Type:</span> {{ $facility->facility_type }}
                            </div>
                        </div>
                    </div>

                    <!-- Second ColumnFacility Details -->
                    <div class="flex-grow space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            <div>
                                <span class="font-medium">Building Name:</span> {{ $facility->building_name }}
                            </div>
                            <div>
                                <span class="font-medium">Floor Level:</span> {{ $facility->floor_level }}
                            </div>
                            <div>
                                <span class="font-medium">Room Number:</span> {{ $facility->room_number }}
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <x-filament::button color="success" wire:click="selectFacility({{ $facility->id }})">
                        Book
                    </x-filament::button>
                </div>
            </x-filament::card>
        @empty
            <x-filament::card>
                <h3 class="text-lg font-medium">No facilities found</h3>
                <p class="mt-1">No facilities found matching "{{ $search }}".</p>
            </x-filament::card>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4 text-black my-4">
        {{ $facilities->links() }}
    </div>
</div>
