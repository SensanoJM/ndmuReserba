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
    <div class="mt-4 text-black">
        {{ $facilities->links() }}
    </div>

    <!-- Modal -->
    @if ($selectedFacility)
        <div id="booking-modal" x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modal-title">
                            Book {{ $selectedFacility->facility_name }}
                        </h3>

                        <form wire:submit.prevent="submit" class="mt-4 space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="booking_date" class="block text-sm font-medium text-gray-700">Booking
                                        Date</label>
                                    <input type="date" id="booking_date" wire:model.defer="booking_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('booking_date')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="start_time" class="block text-sm font-medium text-gray-700">Start
                                        Time</label>
                                    <input type="time" id="start_time" wire:model.defer="start_time"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('start_time')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="end_time" class="block text-sm font-medium text-gray-700">End
                                        Time</label>
                                    <input type="time" id="end_time" wire:model.defer="end_time"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('end_time')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="purpose"
                                        class="block text-sm font-medium text-gray-700">Purpose</label>
                                    <input type="text" id="purpose" wire:model.defer="purpose"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('purpose')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="duration"
                                        class="block text-sm font-medium text-gray-700">Duration</label>
                                    <input type="text" id="duration" wire:model.defer="duration"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('duration')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="participants"
                                        class="block text-sm font-medium text-gray-700">Participants</label>
                                    <input type="text" id="participants" wire:model.defer="participants"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @error('participants')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="policy" class="block text-sm font-medium text-gray-700">Policy</label>
                                <textarea id="policy" wire:model.defer="policy" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                @error('policy')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Equipment Requests</label>
                                @foreach ($equipments as $index => $equipment)
                                    <div class="flex space-x-2 mt-2 gap-2">
                                        <select wire:model.defer="equipments.{{ $index }}.item"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <option value="">Select Item</option>
                                            <option value="plastic_chairs">Plastic Chairs</option>
                                            <option value="long_table">Long Table</option>
                                            <option value="teacher_table">Teacher's Table</option>
                                            <option value="backdrop">Backdrop</option>
                                            <option value="riser">Riser</option>
                                            <option value="armed_chair">Armed Chairs</option>
                                            <option value="pole">Pole</option>
                                            <option value="rostrum">Rostrum</option>
                                        </select>
                                        <input type="number"
                                            wire:model.defer="equipments.{{ $index }}.quantity" min="1"
                                            placeholder="Quantity"
                                            class="block w-1/4 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="button" wire:click="removeEquipment({{ $index }})"
                                            class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Remove
                                        </button>
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addEquipment"
                                    class="mt-2 inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Add Equipment
                                </button>
                            </div>

                            <div>
                                <label for="booking_attachments"
                                    class="block text-sm font-medium text-gray-700">Attachments</label>
                                <input type="file" id="booking_attachments" wire:model.defer="booking_attachments"
                                    class="mt-1 block w-full">
                                @error('booking_attachments')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>    

                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <!-- Book Now Button -->
                                <x-filament::button type="submit" color="success" class="w-full sm:col-start-2">
                                    Book Now
                                </x-filament::button>
                                
                                <!-- Cancel Button -->
                                <x-filament::button type="button" color="danger" class="w-full sm:col-start-2" wire:click="closeModal">
                                    Cancel
                                </x-filament::button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
