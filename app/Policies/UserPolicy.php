<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model)
    {
        return $user->isAdmin();
    }
    // Add other methods as needed
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
}
