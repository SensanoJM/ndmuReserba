<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $approvedBookings = Booking::where('status', 'approved')->get();

        foreach ($approvedBookings as $booking) {
            Reservation::create([
                'booking_id' => $booking->id,
                'status' => 'approved',
                'admin_approval_date' => now()->subDays(rand(5, 10)),
                'final_approval_date' => now()->subDays(rand(1, 4)),
                'director_notified_at' => now()->subDays(rand(4, 7)),
            ]);
        }
    }
}