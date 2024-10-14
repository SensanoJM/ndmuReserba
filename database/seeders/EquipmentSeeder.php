<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipmentList = [
            ['name' => 'Plastic Chairs', 'description' => 'Stackable plastic chairs for events'],
            ['name' => 'Long Table', 'description' => 'Foldable long tables for various uses'],
            ['name' => 'Teacher\'s Table', 'description' => 'Standard teacher\'s desk'],
            ['name' => 'Backdrop', 'description' => 'Portable backdrop for presentations'],
            ['name' => 'Riser', 'description' => 'Portable stage riser'],
            ['name' => 'Armed Chairs', 'description' => 'Chairs with attached writing surfaces'],
            ['name' => 'Pole', 'description' => 'Adjustable pole for various uses'],
            ['name' => 'Rostrum', 'description' => 'Speaker\'s podium'],
        ];

        foreach ($equipmentList as $equipment) {
            Equipment::create($equipment);
        }
    }
}