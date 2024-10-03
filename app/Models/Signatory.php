<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Signatory extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'status',
        'role',
        'email',
        'approval_date',
        'approval_token',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
    ];

    public function approve()
    {
        $this->status = 'approved';
        $this->approval_date = now();
        $this->save();
    }

    public function deny()
    {
        $this->status = 'denied';
        $this->approval_date = now();
        $this->save();
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isDenied()
    {
        return $this->status === 'denied';
    }

    /**
     * Generate a new approval token for the signatory.
     * 
     * This is used to generate a unique token for the signatory to approve or deny
     * the reservation request. The token is persisted to the database and returned
     * from this method.
     * 
     * @return string The generated token.
     */
    private function generateApprovalToken()
    {
        $this->approval_token = Str::random(32);
        $this->save();
        return $this->approval_token;
    }

    public function getApprovalUrlAttribute()
    {
        return route('signatory.approval', [
            'signatory' => $this->id,
            'token' => $this->approval_token ?? $this->generateApprovalToken()
        ]);
    }
    
    public function getDenyUrlAttribute()
    {
        return route('signatory.denial', [
            'signatory' => $this->id,
            'token' => $this->approval_token ?? $this->generateApprovalToken()
        ]);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
