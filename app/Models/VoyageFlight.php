<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageFlight extends Model
{
    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

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
        'voyage_id',
        'direction',
        'airline_id',
        'cabin',
        'flight_number',
        'from_city',
        'to_city',
        'departure_date',
        'baggage_cabin_kg',
        'baggage_checkin_kg',
        'is_tentative',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'is_tentative' => 'boolean',
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

    public function getDepartureDateFormattedAttribute(): ?string
    {
        return $this->departure_date?->format('D, d M');
    }

    public function getCabinBaggageDisplayAttribute(): string
    {
        return $this->baggage_cabin_kg !== null ? (string) $this->baggage_cabin_kg . ' KGS' : '—';
    }

    public function getCheckinBaggageDisplayAttribute(): string
    {
        return $this->baggage_checkin_kg !== null ? (string) $this->baggage_checkin_kg . ' KGS' : '—';
    }

    /**
     * For front partial: array shape expected by flight-card.
     */
    public function toDisplayArray(): array
    {
        return [
            'id' => $this->id,
            'flight_type' => $this->direction,
            'from_city' => $this->from_city ?? '',
            'to_city' => $this->to_city ?? '',
            'depart_date_formatted' => $this->departure_date_formatted ?? '—',
            'arrive_date_formatted' => $this->departure_date_formatted ?? '—',
            'cabin_baggage_display' => $this->cabin_baggage_display,
            'checkin_baggage_display' => $this->checkin_baggage_display,
            'is_tentative' => $this->is_tentative,
        ];
    }
}
