<?php

namespace App\Services;

use App\Models\Approver;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\Facility;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function createBooking(array $data, Facility $facility, int $userId): Booking
    {
        // Use a cache key for checking facility availability
        $availabilityCacheKey = "facility_{$facility->id}_availability_{$data['booking_start']}_{$data['booking_end']}";
        
        try {
            return DB::transaction(function () use ($data, $facility, $userId, $availabilityCacheKey) {
                // Check availability using cached query
                if (!$this->checkAvailability($facility, $data['booking_start'], $data['booking_end'])) {
                    throw new \Exception('Time slot is not available');
                }

                // Create booking with a single query
                $booking = Booking::create([
                    'facility_id' => $facility->id,
                    'booking_start' => $data['booking_start'],
                    'booking_end' => $data['booking_end'],
                    'purpose' => $data['purpose'],
                    'duration' => $data['duration'],
                    'participants' => $data['participants'],
                    'user_id' => $userId,
                    'status' => 'prebooking',
                    'contact_number' => $data['contact_number'],
                ]);

                // Batch insert equipment if present
                if (!empty($data['equipment'])) {
                    $this->processEquipmentBatch($booking, $data['equipment']);
                }

                // Batch insert approvers
                $this->createApproversBatch($booking, $data);

                // Clear availability cache
                Cache::forget($availabilityCacheKey);

                return $booking;
            });
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Check facility availability using cached query
     */
    private function checkAvailability(Facility $facility, string $start, string $end): bool
    {
        $cacheKey = "facility_{$facility->id}_availability_{$start}_{$end}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($facility, $start, $end) {
            return !Booking::where('facility_id', $facility->id)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('booking_start', [$start, $end])
                        ->orWhereBetween('booking_end', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('booking_start', '<=', $start)
                                ->where('booking_end', '>=', $end);
                        });
                })
                ->exists();
        });
    }

    /**
     * Process equipment with batch insertion
     */
    private function processEquipmentBatch(Booking $booking, array $equipmentData): void
    {
        $equipmentRecords = [];
        $timestamp = now();

        // Group and sum quantities for duplicate equipment
        $groupedEquipment = collect($equipmentData)
            ->filter(fn($item) => !empty($item['item']) && !empty($item['quantity']))
            ->groupBy('item')
            ->map(function ($items) {
                return [
                    'item' => $items->first()['item'],
                    'quantity' => $items->sum('quantity')
                ];
            });

        foreach ($groupedEquipment as $equipmentData) {
            // Get or create equipment in bulk
            $equipment = Equipment::firstOrCreate(
                ['name' => $equipmentData['item']],
                ['created_at' => $timestamp, 'updated_at' => $timestamp]
            );

            $equipmentRecords[] = [
                'booking_id' => $booking->id,
                'equipment_id' => $equipment->id,
                'quantity' => $equipmentData['quantity']
            ];
        }

        // Batch insert equipment pivot records
        if (!empty($equipmentRecords)) {
            DB::table('booking_equipment')->insert($equipmentRecords);
        }
    }

    /**
     * Create approvers with batch insertion
     */
    private function createApproversBatch(Booking $booking, array $data): void
    {
        $timestamp = now();
        
        $approvers = [
            [
                'booking_id' => $booking->id,
                'email' => $data['adviser_email'],
                'role' => 'adviser',
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ],
            [
                'booking_id' => $booking->id,
                'email' => $data['dean_email'],
                'role' => 'dean',
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ]
        ];

        Approver::insert($approvers);
    }
}
