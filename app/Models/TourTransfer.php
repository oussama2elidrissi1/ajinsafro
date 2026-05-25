<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourTransfer extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_transfers';

    public const DIRECTION_ARRIVAL = 'arrival';
    public const DIRECTION_DEPARTURE = 'departure';

    protected $fillable = [
        'tour_id',
        'direction',
        'day_number',
        'sort_order',
        'is_optional',
        'from_label',
        'to_label',
        'pickup_time',
        'dropoff_time',
        'vehicle_type',
        'notes',
        'image_id',
        'image_path',
    ];

    protected $casts = [
        'image_id' => 'integer',
        'day_number' => 'integer',
        'is_optional' => 'boolean',
    ];

    public function getPickupTimeFormattedAttribute(): string
    {
        if (empty($this->pickup_time)) {
            return '—';
        }
        if (is_string($this->pickup_time)) {
            return substr($this->pickup_time, 0, 5);
        }
        return $this->pickup_time->format('H:i');
    }

    public function getDropoffTimeFormattedAttribute(): string
    {
        if (empty($this->dropoff_time)) {
            return '—';
        }
        if (is_string($this->dropoff_time)) {
            return substr($this->dropoff_time, 0, 5);
        }
        return $this->dropoff_time->format('H:i');
    }

    /**
     * Get all transfers for a tour, grouped by direction (multi-row support).
     *
     * @return array{arrival: \Illuminate\Support\Collection, departure: \Illuminate\Support\Collection}
     */
    public static function getForTour(int $tourId): array
    {
        $rows = self::where('tour_id', $tourId)
            ->orderByRaw('COALESCE(day_number, 1) ASC')
            ->orderByRaw("CASE direction WHEN 'arrival' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        return [
            'arrival' => $rows->where('direction', self::DIRECTION_ARRIVAL)->values(),
            'departure' => $rows->where('direction', self::DIRECTION_DEPARTURE)->values(),
        ];
    }
}

