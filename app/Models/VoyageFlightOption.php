<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageFlightOption extends Model
{
    public const TYPE_OUTBOUND = 'outbound';
    public const TYPE_RETURN = 'return';
    public const TYPE_SEGMENT = 'segment';

    public const CABIN_ECONOMY = 'economy';
    public const CABIN_BUSINESS = 'business';
    public const CABIN_FIRST = 'first';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_OUTBOUND => 'Vol Aller',
            self::TYPE_RETURN => 'Vol Retour',
            self::TYPE_SEGMENT => 'Vol interne / Segment',
        ];
    }

    public static function cabinOptions(): array
    {
        return [
            self::CABIN_ECONOMY => 'Économique',
            self::CABIN_BUSINESS => 'Business',
            self::CABIN_FIRST => 'First',
        ];
    }

    protected $fillable = [
        'voyage_id',
        'type',
        'day_number',
        'from_city',
        'to_city',
        'depart_at',
        'arrive_at',
        'airline_id',
        'flight_number',
        'cabin',
        'baggage_cabin_kg',
        'baggage_checkin_kg',
        'duration_minutes',
        'stops',
        'price',
        'currency',
        'is_included',
        'is_optional',
        'group_key',
        'sort_order',
        'is_tentative',
        'notes',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_at' => 'datetime',
        'is_included' => 'boolean',
        'is_optional' => 'boolean',
        'is_tentative' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function getFromLabelAttribute(): string
    {
        return $this->from_city ?: '—';
    }

    public function getToLabelAttribute(): string
    {
        return $this->to_city ?: '—';
    }

    public function getDepartAtFormattedAttribute(): ?string
    {
        return $this->depart_at?->format('D, d M');
    }

    public function getArriveAtFormattedAttribute(): ?string
    {
        return $this->arrive_at?->format('D, d M');
    }

    public function getCabinBaggageDisplayAttribute(): string
    {
        return $this->baggage_cabin_kg !== null ? (string) $this->baggage_cabin_kg . ' KGS' : '—';
    }

    public function getCheckinBaggageDisplayAttribute(): string
    {
        return $this->baggage_checkin_kg !== null ? (string) $this->baggage_checkin_kg . ' KGS' : '—';
    }

    /** For front: flight_type compatible with existing card (outbound / inbound). */
    public function getFlightTypeForWpAttribute(): string
    {
        return $this->type === self::TYPE_RETURN ? 'inbound' : 'outbound';
    }
}
