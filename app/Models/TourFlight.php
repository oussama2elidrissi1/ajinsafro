<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourFlight extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_flights';

    public const TYPE_OUTBOUND = 'outbound';
    public const TYPE_INBOUND = 'inbound';

    public const CABIN_ECONOMY = 'economy';
    public const CABIN_BUSINESS = 'business';
    public const CABIN_FIRST = 'first';

    public static function cabinOptions(): array
    {
        return [
            self::CABIN_ECONOMY => 'Économique',
            self::CABIN_BUSINESS => 'Business',
            self::CABIN_FIRST => 'First',
        ];
    }

    protected $fillable = [
        'tour_id',
        'flight_type',
        'airline_id',
        'cabin_class',
        'from_city',
        'to_city',
        'depart_date',
        'depart_time',
        'arrive_date',
        'arrive_time',
        'baggage_cabin_kg',
        'baggage_checkin_kg',
        'is_tentative',
        'notes',
    ];

    protected $casts = [
        'depart_date' => 'date',
        'arrive_date' => 'date',
        'is_tentative' => 'boolean',
    ];

    public function airline(): BelongsTo
    {
        return $this->belongsTo(AjAirline::class, 'airline_id');
    }

    public function getFromLabelAttribute(): string
    {
        return $this->from_city ?: '—';
    }

    public function getToLabelAttribute(): string
    {
        return $this->to_city ?: '—';
    }

    public function getDepartDateFormattedAttribute(): ?string
    {
        return $this->depart_date?->format('D, d M');
    }

    public function getArriveDateFormattedAttribute(): ?string
    {
        return $this->arrive_date?->format('D, d M');
    }

    public function getBaggageCabinDisplayAttribute(): string
    {
        return $this->baggage_cabin_kg !== null ? (string) $this->baggage_cabin_kg . ' KGS' : '—';
    }

    public function getBaggageCheckinDisplayAttribute(): string
    {
        return $this->baggage_checkin_kg !== null ? (string) $this->baggage_checkin_kg . ' KGS' : '—';
    }
}
