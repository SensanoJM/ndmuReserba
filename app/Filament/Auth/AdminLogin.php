<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class AdminLogin extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return __('Admin Login');
    }
}