<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        return [
            'booking_id' => Booking::factory(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'denied']),
            'admin_approval_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'final_approval_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}