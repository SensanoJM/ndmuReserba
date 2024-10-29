<?php

namespace App\Services;

use App\Models\Approver;
use App\Models\Booking;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function createBooking(array $data, Facility $facility, int $userId)
    {
        return DB::transaction(function () use ($data, $facility, $userId) {
            $booking = $this->createBookingRecord($data, $facility, $userId);
            $this->createEquipmentEntries($booking, $data['equipment']);
            $this->createApprovers($booking, $data);

            return $booking;
        });
    }

    private function createBookingRecord(array $data, Facility $facility, int $userId): Booking
    {
        return Booking::create([
            'facility_id' => $facility->id,
            'booking_start' => $data['booking_start'],
            'booking_end' => $data['booking_end'],
            'purpose' => $data['purpose'],
            'duration' => $data['duration'],
            'participants' => $data['participants'],
            'user_id' => $userId,
        ]);
    }

    private function createEquipmentEntries(Booking $booking, array $equipmentData)
    {
        foreach ($equipmentData as $item) {
            $booking->equipment()->create([
                'name' => $item['item'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    private function createApprovers(Booking $booking, array $data)
    {
        Approver::create([
            'booking_id' => $booking->id,
            'email' => $data['adviser_email'],
            'role' => 'adviser',
        ]);

        Approver::create([
            'booking_id' => $booking->id,
            'email' => $data['dean_email'],
            'role' => 'dean',
        ]);
    }
}
