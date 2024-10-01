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
