<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approver extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'email', 'role'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}