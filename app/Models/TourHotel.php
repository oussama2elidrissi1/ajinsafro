<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourHotel extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_hotels';

    protected $fillable = [
        'tour_id',
        'day_number', // Ancien format (compatibilité)
        'check_in_day', // Nouveau format
        'check_out_day', // Nouveau format
        'sort_order',
        'is_optional',
        'hotel_name',
        'stars',
        'address',
        'room_type',
        'meal_plan',
        'notes',
        'image_id',
        'image_path',
    ];

    protected $casts = [
        'stars' => 'integer',
        'image_id' => 'integer',
        'day_number' => 'integer',
        'check_in_day' => 'integer',
        'check_out_day' => 'integer',
        'is_optional' => 'boolean',
    ];

    /** One hotel (backward compat). */
    public static function getForTour(int $tourId): ?self
    {
        return self::where('tour_id', $tourId)->orderBy('sort_order')->orderBy('id')->first();
    }

    /** All hotels for tour (multi-row support), ordered by check_in_day (ou day_number) then sort_order. */
    public static function getAllForTour(int $tourId): \Illuminate\Support\Collection
    {
        return self::where('tour_id', $tourId)
            ->orderByRaw('COALESCE(check_in_day, day_number, 1) ASC')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(TourHotelRoom::class, 'tour_hotel_id')->orderBy('sort_order')->orderBy('id');
    }

    public function roomAvailabilities(): HasMany
    {
        return $this->hasMany(TourHotelRoomAvailability::class, 'tour_hotel_id')
            ->orderBy('travel_date_id')
            ->orderBy('id');
    }
}

