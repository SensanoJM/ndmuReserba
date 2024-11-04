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
        'id_number',
        'department_id',
        'description',
        
    ];

        // Add a method to help with debugging
        public function getRoleDetails(): array
        {
            return [
                'user_id' => $this->id,
                'role' => $this->role,
                'is_admin_check' => $this->isAdmin(),
                'raw_role_comparison' => $this->role === 'admin',
            ];
        }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isFaculty()
    {
        return $this->role === 'faculty';
    }

    public function isStudent()
    {
        return $this->role === 'student';
    }

    public function isSignatory()
    {
        return $this->role === 'signatory'; 
    }

    public function isOrganization()
    {
        return $this->role === 'organization';
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
