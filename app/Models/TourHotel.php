<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourHotel extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_hotels';

    protected $fillable = [
        'tour_id',
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
    ];

    public static function getForTour(int $tourId): ?self
    {
        return self::where('tour_id', $tourId)->first();
    }
}
