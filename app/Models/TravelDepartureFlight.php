<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelDepartureFlight extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_travel_departure_flights';

    protected $fillable = [
        'departure_place_id',
        'airline',
        'flight_number',
        'from_airport',
        'to_airport',
        'depart_time',
        'arrive_time',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'departure_place_id' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Relation vers le lieu de départ parent
     */
    public function departurePlace(): BelongsTo
    {
        return $this->belongsTo(TravelDeparturePlace::class, 'departure_place_id');
    }

    /**
     * Récupérer tous les vols pour un lieu de départ donné
     */
    public static function getFlightsForPlace(int $placeId)
    {
        return self::where('departure_place_id', $placeId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
