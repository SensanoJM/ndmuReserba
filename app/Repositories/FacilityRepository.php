<?php

namespace App\Repositories;

use App\Models\Facility;
use Illuminate\Support\Facades\Cache;
use App\Models\Booking;

class FacilityRepository
{
    public function getAllFacilities()
    {
        return Cache::remember('all_facilities', now()->addDay(), function () {
            return Facility::all();
        });
    }

    public function getFacilityTypes()
    {
        return Cache::remember('facility_types', now()->addDay(), function () {
            return Facility::distinct()->pluck('facility_type', 'facility_type')->toArray();
        });
    }

    public function checkAvailability(Facility $facility, $startTime, $endTime)
    {
        return Booking::where('facility_id', $facility->id)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('booking_start', [$startTime, $endTime])
                    ->orWhereBetween('booking_end', [$startTime, $endTime])
                    ->orWhere(function ($query) use ($startTime, $endTime) {
                        $query->where('booking_start', '<=', $startTime)
                            ->where('booking_end', '>=', $endTime);
                    });
            })
            ->count() === 0;
    }

    public function getCachedFacilityTypes()
    {
        return Cache::remember('facility_types', now()->addDay(), function () {
            return Facility::distinct()->pluck('facility_type', 'facility_type')->toArray();
        });
    }
}