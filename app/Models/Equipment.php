<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($equipment) {
            // Normalize the equipment name before saving
            $equipment->name = strtolower(trim($equipment->name));
        });
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class)
                    ->withPivot('quantity');
    }

    /**
     * Get the formatted name attribute
     */
    public function getFormattedNameAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->name));
    }
}