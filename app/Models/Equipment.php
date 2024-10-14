<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_equipment')
                    ->withPivot('quantity');
    }
}