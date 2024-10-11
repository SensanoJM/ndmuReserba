<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait BookingCacheInvalidation
{
    public static function bootBookingCacheInvalidation()
    {
        static::created(function ($model) {
            self::invalidateCache($model);
        });

        static::updated(function ($model) {
            self::invalidateCache($model);
        });

        static::deleted(function ($model) {
            self::invalidateCache($model);
        });
    }

    private static function invalidateCache($model)
    {
        $date = $model->booking_date;
        $startOfMonth = $date->startOfMonth()->toDateString();
        $endOfMonth = $date->endOfMonth()->toDateString();
        
        $cacheKey = "calendar_events_{$startOfMonth}_{$endOfMonth}";
        Cache::forget($cacheKey);
    }
}
