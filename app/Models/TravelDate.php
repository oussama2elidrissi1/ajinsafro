<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelDate extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_travel_dates';

    protected $fillable = [
        'travel_id',
        'date',
        'is_active',
        'seats',
        'price_override',
    ];

    protected $casts = [
        'travel_id' => 'integer',
        'date' => 'date',
        'is_active' => 'boolean',
        'seats' => 'integer',
        'price_override' => 'decimal:2',
    ];

    /**
     * Récupérer toutes les dates actives pour un voyage/tour donné
     */
    public static function getActiveDatesForTour(int $tourId)
    {
        return self::where('travel_id', $tourId)
            ->where('is_active', true)
            ->orderBy('date')
            ->get();
    }

    public static function getDatesForTour(int $tourId)
    {
        return self::where('travel_id', $tourId)
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Récupérer les dates disponibles formatées pour le calendrier (array simple des dates)
     */
    public static function getAvailableDatesForCalendar(int $tourId): array
    {
        return self::where('travel_id', $tourId)
            ->where('is_active', true)
            ->orderBy('date')
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
    }

    /**
     * Vérifier si une date est disponible pour un tour donné
     */
    public static function isDateAvailable(int $tourId, string $date): bool
    {
        return self::where('travel_id', $tourId)
            ->where('date', $date)
            ->where('is_active', true)
            ->exists();
    }

    public function roomAvailabilities(): HasMany
    {
        return $this->hasMany(TourHotelRoomAvailability::class, 'travel_date_id')
            ->orderBy('tour_hotel_id')
            ->orderBy('tour_hotel_room_id');
    }
}
