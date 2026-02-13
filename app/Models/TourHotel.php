<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourHotel extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_hotels';

    protected $fillable = [
        'tour_id',
        'day_number',
        'sort_order',
        'is_optional',
        'hotel_name',
        'stars',
        'address',
        'room_type',
        'meal_plan',
        'notes',
        'image_id',
    ];

    protected $casts = [
        'stars' => 'integer',
        'image_id' => 'integer',
        'day_number' => 'integer',
        'is_optional' => 'boolean',
    ];

    /** One hotel (backward compat). */
    public static function getForTour(int $tourId): ?self
    {
        return self::where('tour_id', $tourId)->orderBy('sort_order')->orderBy('id')->first();
    }

    /** All hotels for tour (multi-row support), ordered by day_number then sort_order. */
    public static function getAllForTour(int $tourId): \Illuminate\Support\Collection
    {
        return self::where('tour_id', $tourId)
            ->orderByRaw('COALESCE(day_number, 1) ASC')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
