<?php

namespace Database\Factories;

use App\Models\Signatory;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SignatoryFactory extends Factory
{
    protected $model = Signatory::class;

    public function definition()
    {
        return [
            'reservation_id' => Reservation::factory(),
            'role' => $this->faker->randomElement(['approver', 'reviewer', 'observer']),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'denied']),
            'approval_date' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'email' => $this->faker->unique()->safeEmail(),
            'approval_token' => Str::random(64),
        ];
    }
}