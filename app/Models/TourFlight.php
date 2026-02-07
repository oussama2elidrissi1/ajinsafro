<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourFlight extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_flights';

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
        'segment_number',
        'airline_id',
        'cabin_class',
        'flight_number',
        'depart_date',
        'depart_city',
        'depart_airport',
        'arrive_date',
        'arrive_city',
        'arrive_airport',
        'cabin_baggage',
        'checkin_baggage',
        'is_tentative',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'depart_date' => 'date',
        'arrive_date' => 'date',
        'is_tentative' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function airline(): BelongsTo
    {
        return $this->belongsTo(AjAirline::class, 'airline_id');
    }

    public function getDepartLabelAttribute(): string
    {
        return $this->depart_city ?: $this->depart_airport ?: '—';
    }

    public function getArriveLabelAttribute(): string
    {
        return $this->arrive_city ?: $this->arrive_airport ?: '—';
    }

    public function getDepartDateFormattedAttribute(): ?string
    {
        return $this->depart_date?->format('D, d M');
    }

    public function getArriveDateFormattedAttribute(): ?string
    {
        return $this->arrive_date?->format('D, d M');
    }

    public function getCabinBaggageDisplayAttribute(): string
    {
        return $this->cabin_baggage ?: '—';
    }

    public function getCheckinBaggageDisplayAttribute(): string
    {
        return $this->checkin_baggage ?: '—';
    }
}
