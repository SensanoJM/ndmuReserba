<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition()
    {
        $startTime = $this->faker->dateTimeBetween('-1 year', 'now');
        $endTime = $this->faker->dateTimeBetween($startTime, '+1 week');

        return [
            'purpose' => $this->faker->sentence,
            'duration' => $this->faker->numberBetween(1, 8) . ' hours',
            'participants' => $this->faker->numberBetween(5, 100),
            'booking_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'booking_attachments' => $this->faker->optional()->passthrough(json_encode(['file1.pdf', 'file2.doc'])),
            'equipment' => $this->faker->optional()->passthrough(json_encode(['projector', 'microphone'])),
            'policy' => $this->faker->optional()->word,
            'status' => $this->faker->randomElement(['pending', 'in_review', 'approved', 'denied']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'adviser_email' => $this->faker->email,
            'dean_email' => $this->faker->email,
            'user_id' => User::factory(),
            'facility_id' => Facility::factory(),
        ];
    }
}