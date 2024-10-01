<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * php artisan migrate:refresh --seed
     */
    public function run(): void
    {
        // Seed Users
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'position' => null,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'user',
            'position' => null,
        ]);

        User::factory()->create([
            'name' => 'Test Signatory',
            'email' => 'signatory@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'signatory',
            'position' => 'school_director',
        ]);

        User::factory()->create([
            'name' => 'Test President',
            'email' => 'sensanomarlu@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'signatory',
            'position' => 'school_president',
        ]);

        // Invoke other seeders here
        $this->call([
            FacilitySeeder::class, // Seeds facilities
            BookingSeeder::class, // Seeds bookings
        ]);

    }
}
