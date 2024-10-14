<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Facility;
use App\Models\Approver;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $facilities = Facility::all();

        // Create approved bookings
        for ($i = 0; $i < 10; $i++) {
            $this->createBooking($users, $facilities, 'approved');
        }

        // Create pending bookings
        for ($i = 0; $i < 15; $i++) {
            $this->createBooking($users, $facilities, 'pending');
        }
    }

    private function createBooking($users, $facilities, $status)
    {
        $startDate = Carbon::now()->addDays(rand(1, 30));
        $endDate = (clone $startDate)->addHours(rand(1, 4));

        $booking = Booking::create([
            'purpose' => ucfirst($status) . ' Event ' . rand(1000, 9999),
            'duration' => $startDate->diffInHours($endDate) . ' hours',
            'participants' => rand(5, 50),
            'booking_date' => $startDate->toDateString(),
            'policy' => 'Standard event policy applies',
            'status' => $status,
            'start_time' => $startDate->toTimeString(),
            'end_time' => $endDate->toTimeString(),
            'user_id' => $users->random()->id,
            'facility_id' => $facilities->random()->id,
        ]);

        // Create approvers for the booking
        $this->createApprovers($booking);
    }

    /**
     * Create approvers for a booking.
     *
     * The approvers are: adviser and dean.
     *
     * @param Booking $booking
     * @return void
     */
    private function createApprovers($booking)
    {
        $approverRoles = ['adviser', 'dean'];

        foreach ($approverRoles as $role) {
            Approver::create([
                'booking_id' => $booking->id,
                'email' => 'sensanomarlu@gmail.com',
                'role' => $role,
            ]);
        }
    }
}