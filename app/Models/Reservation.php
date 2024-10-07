<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'status',
        'admin_approval_date',
        'final_approval_date',
        'director_notified_at',
    ];

    protected $casts = [
        'admin_approval_date' => 'datetime',
        'final_approval_date' => 'datetime',
        'director_notified_at' => 'datetime',

    ];

    /**
     * Check if all non-director signatories have approved the reservation.
     *
     * @return bool
     */
    public function allNonDirectorSignatoriesApproved()
    {
        $nonDirectorSignatories = $this->signatories()->where('role', '!=', 'school_director');
        $approvedCount = $nonDirectorSignatories->where('status', 'approved')->count();

        return $approvedCount === $nonDirectorSignatories->count();
    }

    /**
     * Checks if all signatories have approved the reservation.
     *
     * This method uses the "doesn't exist" query builder method to check if there are any
     * signatories with a status other than 'approved'. If no such record exists, it means
     * all signatories have approved the reservation.
     *
     * @return bool
     */
    public function allSignatoriesApproved()
    {
        return $this->signatories()->where('status', '!=', 'approved')->doesntExist();
    }

    /**
     * Checks if the director signatory has been notified.
     *
     * @return bool
     */
    public function directorNotified()
    {
        return !is_null($this->director_notified_at);
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
