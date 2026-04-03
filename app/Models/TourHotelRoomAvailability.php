<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourHotelRoomAvailability extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LIMITED = 'limited';
    public const STATUS_FULL = 'full';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_LIMITED,
        self::STATUS_FULL,
        self::STATUS_CLOSED,
    ];

    protected $connection = 'wp';

    protected $table = 'aj_tour_hotel_room_date_availabilities';

    protected $fillable = [
        'tour_id',
        'tour_hotel_id',
        'tour_hotel_room_id',
        'travel_date_id',
        'available_rooms',
        'available_places',
        'status',
        'supplement',
    ];

    protected $casts = [
        'tour_id' => 'integer',
        'tour_hotel_id' => 'integer',
        'tour_hotel_room_id' => 'integer',
        'travel_date_id' => 'integer',
        'available_rooms' => 'integer',
        'available_places' => 'integer',
        'supplement' => 'decimal:2',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(TourHotel::class, 'tour_hotel_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(TourHotelRoom::class, 'tour_hotel_room_id');
    }

    public function travelDate(): BelongsTo
    {
        return $this->belongsTo(TravelDate::class, 'travel_date_id');
    }
}
