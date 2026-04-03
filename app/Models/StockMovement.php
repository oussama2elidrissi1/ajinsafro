<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_HOLD = 'hold';

    public const TYPE_CONSUME = 'consume';

    public const TYPE_RELEASE = 'release';

    public const TYPE_ADJUST = 'adjust';

    protected $fillable = [
        'reservation_id',
        'departure_id',
        'departure_hotel_room_id',
        'movement_type',
        'rooms_delta',
        'places_delta',
        'before_state',
        'after_state',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'departure_id' => 'integer',
        'departure_hotel_room_id' => 'integer',
        'rooms_delta' => 'integer',
        'places_delta' => 'integer',
        'before_state' => 'array',
        'after_state' => 'array',
        'created_by' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function departureHotelRoom(): BelongsTo
    {
        return $this->belongsTo(DepartureHotelRoom::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
