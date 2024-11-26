<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Facility;
use App\Models\Approver;
use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $facilities = Facility::all();

        // Create approved bookings for the calendar
        $this->createBookingsWithStatus('approved', $users, $facilities, 10);

        // Create prebooking status bookings for testing email process
        $this->createBookingsWithStatus('prebooking', $users, $facilities, 5);
    }

    private function createBookingsWithStatus($status, $users, $facilities, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            $booking = $this->createBooking($users, $facilities, $status);
            $this->createApprovers($booking);

            if ($status === 'approved') {
                $reservation = $this->createReservation($booking, $status);
                $this->createSignatories($reservation, $status);
                
                // Set pdfNotificationSent to true for approved bookings
                $booking->update(['pdfNotificationSent' => true]);
            }
        }
    }

    private function createBooking($users, $facilities, $status)
    {
        $facility = $facilities->random();
        $bookingStart = $this->getAvailableStartDateTime($facility);
        $bookingEnd = (clone $bookingStart)->addHours(rand(1, 4));

        return Booking::create([
            'purpose' => ucfirst($status) . ' Event ' . rand(1000, 9999),
            'duration' => $bookingStart->diffInHours($bookingEnd) . ' hours',
            'participants' => rand(5, 50),
            'booking_start' => $bookingStart,
            'booking_end' => $bookingEnd,
            'status' => $status,
            'user_id' => $users->random()->id,
            'facility_id' => $facility->id,
            'pdfNotificationSent' => false, // Default to false for new bookings
            'contact_number' => $this->generateRandomPhoneNumber(),
        ]);
    }

    private function generateRandomPhoneNumber()
    {
        // Generates a random phone number in the format (XXX) XXX-XXXX
        return sprintf(
            "(%03d) %03d-%04d", 
            rand(100, 999),  // Area code
            rand(100, 999),  // First three digits
            rand(0, 9999)    // Last four digits
        );
    }

    private function createReservation($booking, $status)
    {
        return Reservation::create([
            'booking_id' => $booking->id,
            'status' => $status,
            'admin_approval_date' => now(),
            'final_approval_date' => now(),
        ]);
    }

    private function createSignatories($reservation, $status)
    {
        $roles = ['adviser', 'dean', 'school_president', 'school_director'];
        
        foreach ($roles as $role) {
            Signatory::create([
                'reservation_id' => $reservation->id,
                'role' => $role,
                'email' => 'signatory_' . $role . '@example.com',
                'status' => 'approved',
                'approval_date' => now(),
                'approval_token' => Str::random(32),
            ]);
        }
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

    private function getAvailableStartDateTime($facility)
    {
        $startDateTime = Carbon::now()->addDays(rand(1, 30))->setTime(rand(8, 20), 0, 0);

        while ($this->isTimeSlotConflicting($facility, $startDateTime)) {
            $startDateTime->addHour();
        }

        return $startDateTime;
    }

    private function isTimeSlotConflicting($facility, $startDateTime)
    {
        $endDateTime = (clone $startDateTime)->addHours(4);

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
}