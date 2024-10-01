<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a new booking
        Booking::create([
            'purpose' => 'Test booking',
            'duration' => '2 hours',
            'participants' => 10,
            'booking_date' => '2024-08-20',
            'booking_attachments' => null,
            'equipment' => null,
            'policy' => null,
            'status' => 'pending',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'user_id' => 1, 
            'facility_id' => 1, // Assuming you have a facility with id 1
            'adviser_email' => 'sensanomarlu@gmail.com',
            'dean_email' => 'sensanomarlu@gmail.com',
        ]);

        // Classroom Reservation
        Booking::create([
            'purpose' => 'Math club meeting',
            'duration' => '3 hours',
            'participants' => 25,
            'booking_date' => '2024-12-05',
            'booking_attachments' => null,
            'equipment' => null,
            'policy' => 'No food allowed in the classroom',
            'status' => 'pending',
            'start_time' => '14:00:00',
            'end_time' => '17:00:00',
            'user_id' => 2, 
            'facility_id' => 2, // Assuming you have a classroom with id 1
            'adviser_email' => 'sensanomarlu@gmail.com',
            'dean_email' => 'sensanomarlu@gmail.com',
        ]);

        // Auditorium Booking for Seminar
        Booking::create([
            'purpose' => 'Career Guidance Seminar',
            'duration' => '4 hours',
            'participants' => 150,
            'booking_date' => '2024-10-12',
            'booking_attachments' => null,
            'equipment' => null,
            'policy' => 'Follow safety protocols',
            'status' => 'pending',
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'user_id' => 1, 
            'facility_id' => 1, // Assuming you have an auditorium with id 2
            'adviser_email' => 'sensanomarlu@gmail.com',
            'dean_email' => 'sensanomarlu@gmail.com',
        ]);

        // Sports Hall Reservation for Basketball Practice
        Booking::create([
            'purpose' => 'Basketball practice',
            'duration' => '2 hours',
            'participants' => 15,
            'booking_date' => '2024-10-20',
            'booking_attachments' => null,
            'equipment' => null,
            'policy' => 'No spectators allowed',
            'status' => 'pending',
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'user_id' => 2, 
            'facility_id' => 3, // Assuming you have a sports hall with id 3
            'adviser_email' => 'sensanomarlu@gmail.com',
            'dean_email' => 'sensanomarlu@gmail.com',
        ]);

        // Lab Room Reservation for Research
        Booking::create([
            'purpose' => 'Chemistry research project',
            'duration' => '5 hours',
            'participants' => 5,
            'booking_date' => '2024-10-15',
            'booking_attachments' => null,
            'equipment' => null,
            'policy' => 'Lab coat required',
            'status' => 'pending',
            'start_time' => '08:00:00',
            'end_time' => '13:00:00',
            'user_id' => 1, 
            'facility_id' => 4, // Assuming you have a lab with id 4
            'adviser_email' => 'sensanomarlu@gmail.com',
            'dean_email' => 'sensanomarlu@gmail.com',
        ]);
    }
}
