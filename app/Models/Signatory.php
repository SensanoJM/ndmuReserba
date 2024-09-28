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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($signatory) {
            $signatory->approval_token = Str::random(64);
        });
    }

    public function getApprovalUrlAttribute()
    {
        return route('signatory.approval', ['signatory' => $this->id]);
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
