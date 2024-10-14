<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SignatorySeeder extends Seeder
{
    public function run(): void
    {
        $approvedReservations = Reservation::where('status', 'approved')->get();
        $signatoryUsers = User::where('role', 'signatory')->get();

        $roles = ['adviser', 'dean', 'school_president', 'school_director'];

        foreach ($approvedReservations as $reservation) {
            foreach ($roles as $role) {
                Signatory::create([
                    'reservation_id' => $reservation->id,
                    'role' => $role,
                    'user_id' => $signatoryUsers->random()->id,
                    'status' => 'approved',
                    'approval_date' => now()->subDays(rand(1, 7)),
                    'email' => $role . '@example.com',
                    'approval_token' => Str::random(32),
                ]);
            }
        }
    }
}