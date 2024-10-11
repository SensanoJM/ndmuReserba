<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PendingBookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $facilities = Facility::all();

        // Create some pending bookings for the near future
        for ($i = 0; $i < 15; $i++) {
            $startDate = Carbon::now()->addDays(rand(1, 14)); // Next two weeks
            $endDate = (clone $startDate)->addHours(rand(1, 4));

            Booking::create([
                'purpose' => 'Pending Event ' . ($i + 1),
                'duration' => $startDate->diffInHours($endDate) . ' hours',
                'participants' => rand(5, 50),
                'booking_date' => $startDate->toDateString(),
                'booking_attachments' => null,
                'equipment' => json_encode(['chairs' => rand(10, 30), 'tables' => rand(2, 5)]),
                'policy' => 'Standard event policy applies',
                'status' => 'pending', // Set status to pending
                'start_time' => $startDate->toTimeString(),
                'end_time' => $endDate->toTimeString(),
                'user_id' => $users->random()->id,
                'facility_id' => $facilities->random()->id,
                'adviser_email' => 'sensanomarlu@gmail.com',
                'dean_email' => 'sensanomarlu@gmail.com',
            ]);
        }
    }
}