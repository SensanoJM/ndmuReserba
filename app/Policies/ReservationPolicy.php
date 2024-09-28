<?php

namespace App\Policies;

use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user)
    {
        return $user->role === 'signatory' || $user->role === 'admin';
    }
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
}
