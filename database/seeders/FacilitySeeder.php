<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Facility;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Facilities
        Facility::create([
            'facility_name' => 'Auditorium',
            'facility_type' => 'Indoor',
            'capacity' => 500,
            'building_name' => 'Main Building',
            'floor_level' => 1,
            'room_number' => 'A101',
            'description' => 'A large auditorium for events and seminars.',
            'facility_image' => 'facility-images\lakecentralhs13.jpg',
            'status' => true,
        ]);

        Facility::create([
            'facility_name' => 'Computer Lab',
            'facility_type' => 'Lab',
            'capacity' => 30,
            'building_name' => 'Science Building',
            'floor_level' => 2,
            'room_number' => 'B202',
            'description' => 'A computer lab with 30 workstations.',
            'facility_image' => 'facility-images\Contemporary_Computer_Lab.jpg',
            'status' => true,
        ]);

        Facility::create([
            'facility_name' => 'Gymnasium',
            'facility_type' => 'Indoor',
            'capacity' => 300,
            'building_name' => 'Sports Complex',
            'floor_level' => 1,
            'room_number' => 'G101',
            'description' => 'A fully equipped gymnasium for sports and events.',
            'facility_image' => 'facility-images\rolfs_athletic_hall_27_feature.jpg',
            'status' => true,
        ]);

        Facility::create([
            'facility_name' => 'Classroom 155',
            'facility_type' => 'Classroom',
            'capacity' => 40,
            'building_name' => 'Academic Building',
            'floor_level' => 1,
            'room_number' => 'C102',
            'description' => 'A standard classroom equipped with modern teaching aids.',
            'facility_image' => 'facility-images\c1.jpg',
            'status' => true,
        ]);

        Facility::create([
            'facility_name' => 'Classroom 101',
            'facility_type' => 'Classroom',
            'capacity' => 40,
            'building_name' => 'Academic Building',
            'floor_level' => 1,
            'room_number' => 'C101',
            'description' => 'A standard classroom equipped with modern teaching aids.',
            'facility_image' => 'facility-images\Classroom-2.jpg',
            'status' => true,
        ]);
    }
}
