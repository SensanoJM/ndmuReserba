<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition()
    {
        return [
            'facility_name' => $this->faker->unique()->words(3, true),
            'facility_type' => $this->faker->randomElement(['Classroom', 'Laboratory', 'Conference Room', 'Auditorium', 'Gym']),
            'capacity' => $this->faker->numberBetween(10, 500),
            'building_name' => $this->faker->company(),
            'floor_level' => $this->faker->numberBetween(1, 10),
            'room_number' => $this->faker->bothify('##??'),
            'description' => $this->faker->paragraph(),
            'facility_image' => $this->faker->optional()->imageUrl(640, 480, 'building'),
            'status' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }
}