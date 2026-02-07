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
        'from_label',
        'to_label',
        'pickup_time',
        'dropoff_time',
        'vehicle_type',
        'notes',
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

    public static function getForTour(int $tourId): array
    {
        $arrival = self::where('tour_id', $tourId)->where('direction', self::DIRECTION_ARRIVAL)->first();
        $departure = self::where('tour_id', $tourId)->where('direction', self::DIRECTION_DEPARTURE)->first();
        return [
            'arrival' => $arrival,
            'departure' => $departure,
        ];
    }
}
