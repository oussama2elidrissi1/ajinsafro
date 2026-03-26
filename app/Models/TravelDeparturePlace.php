<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelDeparturePlace extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_travel_departure_places';

    protected $fillable = [
        'travel_id',
        'name',
        'code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'travel_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relation vers les vols de départ associés à ce lieu
     */
    public function flights(): HasMany
    {
        return $this->hasMany(TravelDepartureFlight::class, 'departure_place_id')->orderBy('sort_order');
    }

    /**
     * Récupérer tous les lieux de départ actifs pour un voyage/tour donné
     */
    public static function getActivePlacesForTour(int $tourId)
    {
        return self::where('travel_id', $tourId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['flights' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            }])
            ->get();
    }

    /**
     * Vérifier si le lieu a au moins un vol
     */
    public function hasFlights(): bool
    {
        return $this->flights()->count() > 0;
    }
}
