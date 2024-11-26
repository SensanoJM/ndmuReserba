<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Facility;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacilitySeeder extends Seeder
{
    public function run()
    {
        $facilities = [
            [
                'facility_name' => 'Business Building Lobby Floor 1',
                'facility_type' => 'Open Area',
                'capacity' => 100,
                'building_name' => 'Business Office Building',
                'floor_level' => 1,
                'room_number' => 'N/A',
                'description' => 'Large open area venue for major and minor events and conferences.',
                'facility_image' => 'facility-images/bo_floor1.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'Business Building Lobby Floor 3',
                'facility_type' => 'Open Area',
                'capacity' => 100,
                'building_name' => 'Business Office Building',
                'floor_level' => 3,
                'room_number' => 'N/A',
                'description' => 'Large open area venue for major and minor events and conferences.',
                'facility_image' => 'facility-images/bo_floor3.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'Teston Computer Lab 3017',
                'facility_type' => 'Laboratory',
                'capacity' => 40,
                'building_name' => 'Teston Building',
                'floor_level' => 3,
                'room_number' => '3017',
                'description' => 'Computer lab with high-speed internet.',
                'facility_image' => 'facility-images/com_lab.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'CBA Conference Hall',
                'facility_type' => 'Conference Hall',
                'capacity' => 80,
                'building_name' => 'Sta. Theresa Hall',
                'floor_level' => 1,
                'room_number' => 'N/A',
                'description' => 'Medium capacity conference hall for meetings and special events.',
                'facility_image' => 'facility-images/cba_auditorium.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'NDMU Gym',
                'facility_type' => 'Open Area',
                'capacity' => 400,
                'building_name' => 'NDMU Gymnasium Building',
                'floor_level' => 1,
                'room_number' => 'N/A',
                'description' => 'Large gymnasium for sports events and student activities.',
                'facility_image' => 'facility-images/ndmu_gym.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'Ohmer Classroom 2001',
                'facility_type' => 'Classroom',
                'capacity' => 25,
                'building_name' => 'Ohmer Building',
                'floor_level' => 1,
                'room_number' => '2001',
                'description' => 'Small classroom for students with limited space.',
                'facility_image' => 'facility-images/classroom_cba.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'SMC Hall',
                'facility_type' => 'Auditorium',
                'capacity' => 150,
                'building_name' => 'SMC Building',
                'floor_level' => 1,
                'room_number' => 'N/A',
                'description' => 'Large lecture/event hall with tiered seating.',
                'facility_image' => 'facility-images/smc_hall.jpg', // You can change this filename
                'status' => true,
            ],
            [
                'facility_name' => 'St. Theresa Classroom 1001',
                'facility_type' => 'Classroom',
                'capacity' => 25,
                'building_name' => 'St. Theresa Building',
                'floor_level' => 1,
                'room_number' => '1001',
                'description' => 'Small classroom for students with limited space.',
                'facility_image' => 'facility-images/classroom_csd.jpg', // You can change this filename
                'status' => true,
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }
    }
}