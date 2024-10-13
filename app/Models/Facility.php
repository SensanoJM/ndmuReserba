<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_image',
        'facility_name',
        'facility_type',
        'capacity',
        'building_name',
        'floor_level',
        'room_number',
        'description',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'floor_level' => 'integer',
    ];

    public function reservation()
    {
        return $this->hasMany(Reservation::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
