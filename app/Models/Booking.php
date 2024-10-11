<?php

namespace App\Models;

use App\Traits\BookingCacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory, BookingCacheInvalidation;

    protected $fillable = [
        'user_id',
        'facility_id',
        'booking_date',
        'start_time',
        'end_time',
        'purpose',
        'duration',
        'participants',
        'policy',
        'equipment',
        'booking_attachments',
        'adviser_email',
        'dean_email',
        'status',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'equipment' => 'array',
        'participants' => 'integer',
        'booking_attachments' => 'array',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }

    // In App\Models\Booking.php

    public function getFormattedSignatoriesAttribute()
    {
        if (!$this->reservation || !$this->reservation->signatories) {
            return 'No approvals yet';
        }

        return $this->reservation->signatories->map(function ($signatory) {
            $userName = $signatory->user ? $signatory->user->name : 'Unknown User';
            $status = ucfirst($signatory->status);
            $approvalDate = $signatory->approval_date
            ? $signatory->approval_date->format('Y-m-d H:i')
            : 'Not approved yet';

            return "{$userName} ({$signatory->role}): {$status} on {$approvalDate}";
        })->join("\n");
    }
}
