<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function reservation()
    {
        return $this->hasMany(Reservation::class);
    }
}
