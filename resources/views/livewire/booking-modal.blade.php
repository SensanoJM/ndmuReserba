<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-md p-6 bg-white rounded shadow-lg">
                <h2 class="mb-4 text-xl font-semibold">Book Facility: {{ $selectedFacility->facility_name }}</h2>

                @if (!empty($this->availabilityMessage))
                    <div class="{{ $this->isAvailable ? 'text-green-600' : 'text-red-600' }} mb-4">
                        {{ $this->availabilityMessage }}
                    </div>
                @endif

                {{ $this->form }}
            </div>
        </div>
    @endif
</div>