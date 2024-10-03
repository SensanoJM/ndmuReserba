<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'status',
        'admin_approval_date',
        'final_approval_date',
    ];

    protected $casts = [
        'admin_approval_date' => 'datetime',
        'final_approval_date' => 'datetime',
    ];

    /**
     * Check if all non-director signatories have approved the reservation.
     *
     * @return bool
     */
    public function allNonDirectorSignatoriesApproved()
    {
        $nonDirectorSignatories = $this->signatories()->where('role', '!=', 'director');
        $approvedCount = $nonDirectorSignatories->where('status', 'approved')->count();
        
        return $approvedCount === $nonDirectorSignatories->count();
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function signatories()
    {
        return $this->hasMany(Signatory::class);
    }
}
