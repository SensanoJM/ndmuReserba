<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Facility;
use App\Models\Approver;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Enums\BookingStatus;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $facilities = Facility::all();

        // Create some approved bookings for the current month
        for ($i = 0; $i < 7; $i++) {
            $this->createBooking($users, $facilities, 'approved');
        }

        // Create some pending bookings
        for ($i = 0; $i < 7; $i++) {
            $this->createBooking($users, $facilities, 'pending');
        }
    }

    /**
     * Creates a booking with the given status and randomly assigns a facility,
     * start and end times, number of participants, and user.
     *
     * @param \Illuminate\Support\Collection $users
     * @param \Illuminate\Support\Collection $facilities
     * @param string $status
     *
     * @return void
     */
    private function createBooking($users, $facilities, $status)
    {
        $facility = $facilities->random();
        $bookingStart = $this->getAvailableStartDateTime($facility);
        $bookingEnd = (clone $bookingStart)->addHours(rand(1, 4));

        $booking = Booking::create([
            'purpose' => ucfirst($status) . ' Event ' . rand(1000, 9999),
            'duration' => $bookingStart->diffInHours($bookingEnd) . ' hours',
            'participants' => rand(5, 50),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'status' => $status,
            'user_id' => $users->random()->id,
            'facility_id' => $facility->id,
        ]);

        $this->createApprovers($booking);
    }

    private function getAvailableStartDateTime($facility)
    {
        $startDateTime = Carbon::now()->addDays(rand(1, 30))->setTime(rand(8, 20), 0, 0); // Set hours between 8 AM and 8 PM

        while ($this->isTimeSlotConflicting($facility, $startDateTime)) {
            $startDateTime->addHour();
        }

        return $startDateTime;
    }

    private function isTimeSlotConflicting($facility, $startDateTime)
    {
        $endDateTime = (clone $startDateTime)->addHours(4); // Maximum duration of 4 hours

        return Booking::where('facility_id', $facility->id)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('booking_start', [$startDateTime, $endDateTime])
                    ->orWhereBetween('booking_end', [$startDateTime, $endDateTime])
                    ->orWhere(function ($query) use ($startDateTime, $endDateTime) {
                        $query->where('booking_start', '<=', $startDateTime)
                            ->where('booking_end', '>=', $endDateTime);
                    });
            })
            ->exists();
    }

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