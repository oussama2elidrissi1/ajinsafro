<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageFlight extends Model
{
    public const CABIN_ECONOMY = 'economy';
    public const CABIN_PREMIUM_ECONOMY = 'premium_economy';
    public const CABIN_BUSINESS = 'business';
    public const CABIN_FIRST = 'first';

    public static function cabinOptions(): array
    {
        return [
            self::CABIN_ECONOMY => 'Économique',
            self::CABIN_PREMIUM_ECONOMY => 'Premium Économique',
            self::CABIN_BUSINESS => 'Business',
            self::CABIN_FIRST => 'First',
        ];
    }

    protected $fillable = [
        'voyage_id',
        'airline_id',
        'cabin_class',
        'flight_number',
        'departure_airport',
        'arrival_airport',
        'departure_at',
        'arrival_at',
        'baggage',
        'price',
        'currency',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
        'is_default' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }
}
