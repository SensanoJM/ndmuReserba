<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BookingEquipment extends Pivot
{
    public $timestamps = false; // Explicitly disable timestamps
    
    protected $table = 'booking_equipment';

    protected $fillable = [
        'booking_id',
        'equipment_id',
        'quantity'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}