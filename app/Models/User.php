<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'role',
        'position',
    ];

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Checks if the user has a signatory role.
     *
     * @return bool
     */
    public function isSignatory()
    {
        return $this->role === 'signatory';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function booking()
    {
        return $this->hasMany(Booking::class);
    }

    // Define relationship with Signatory
    public function signatories()
    {
        return $this->hasMany(Signatory::class);
    }
}
