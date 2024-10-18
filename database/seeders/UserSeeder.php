<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Invoke other seeders here
        $this->call([
            DepartmentSeeder::class, // Seeds departments
            EquipmentSeeder::class, // Seeds equipment
            FacilitySeeder::class, // Seeds facilities
        ]);

        // Seed Users
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'position' => null,
            'description' => null,
            'id_number' => '2022391',
            'department_id' => null, 
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'user',
            'position' => null,
            'description' => null,
            'id_number' => '2024329',
            'department_id' => Department::where('name', 'College of Arts and Science')->first()->id, // Assign a department
        ]);

        User::factory()->create([
            'name' => 'Test Signatory',
            'email' => 'sensanomarlu@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'signatory',
            'position' => 'school_director',
            'description' => null,
            'id_number' => '20222335',
            'department_id' => null,  
        ]);

        User::factory()->create([
            'name' => 'Test President',
            'email' => 'sensanomarlu@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'signatory',
            'position' => 'school_president',
            'description' => null,
            'id_number' => '20242021',
            'department_id' => null, 
        ]);

        User::factory()->create([
            'name' => 'Test Organization',
            'email' => 'sensanomarlu@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'organization',
            'position' => null,
            'description' => 'This is a test organization account. Please do not use it in production.
                     It is for testing purposes only. It is not intended for production use.',
            'id_number' => null,
            'department_id' => null,
        ]);
    }
}
