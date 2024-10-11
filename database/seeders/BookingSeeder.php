<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Signatory;
use App\Models\Reservation;
use Illuminate\Support\Str;
use App\Models\Facility;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $facilities = Facility::all();

        // Create some approved bookings for the current month
        for ($i = 0; $i < 7; $i++) {
            $startDate = Carbon::now()->addDays(rand(1, 30));
            $endDate = (clone $startDate)->addHours(rand(1, 4));

            $booking = Booking::create([
                'purpose' => 'Seeded Event ' . ($i + 1),
                'duration' => $startDate->diffInHours($endDate) . ' hours',
                'participants' => rand(5, 50),
                'booking_date' => $startDate->toDateString(),
                'booking_attachments' => null,
                'equipment' => null,
                'policy' => null,
                'status' => 'approved', // Set status to approved
                'start_time' => $startDate->toTimeString(),
                'end_time' => $endDate->toTimeString(),
                'user_id' => $users->random()->id,
                'facility_id' => $facilities->random()->id,
                'adviser_email' => 'adviser@example.com',
                'dean_email' => 'dean@example.com',
            ]);

            // Create an associated reservation
            $reservation = Reservation::create([
                'booking_id' => $booking->id,
                'status' => 'approved',
                'admin_approval_date' => now(),
                'final_approval_date' => now(),
            ]);

            // Create associated signatories
            $signatoryRoles = ['adviser', 'dean', 'school_president', 'school_director'];
            foreach ($signatoryRoles as $role) {
                Signatory::create([
                    'reservation_id' => $reservation->id,
                    'role' => $role,
                    'user_id' => $users->where('role', 'signatory')->random()->id,
                    'status' => 'approved',
                    'approval_date' => now(),
                    'email' => $role . '@example.com',
                    'approval_token' => Str::random(32),
                ]);
            }
        }
    }
}
