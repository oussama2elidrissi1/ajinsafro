<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationRoom extends Model
{
    protected $table = 'reservation_rooms';

    protected $fillable = [
        'reservation_id',
        'departure_hotel_room_id',
        'departure_hotel_id',
        'room_type_snapshot',
        'passenger_count',
        'tour_hotel_id',
        'tour_hotel_room_id',
        'room_count',
        'supplement_unit',
        'supplement_total',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'departure_hotel_room_id' => 'integer',
        'departure_hotel_id' => 'integer',
        'passenger_count' => 'integer',
        'tour_hotel_id' => 'integer',
        'tour_hotel_room_id' => 'integer',
        'room_count' => 'integer',
        'supplement_unit' => 'decimal:2',
        'supplement_total' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation vers l'hôtel du voyage (table wp).
     */
    public function tourHotel(): BelongsTo
    {
        return $this->belongsTo(TourHotel::class, 'tour_hotel_id');
    }

    /**
     * Relation vers le type de chambre (table wp).
     */
    public function tourHotelRoom(): BelongsTo
    {
        return $this->belongsTo(TourHotelRoom::class, 'tour_hotel_room_id');
    }

    public function departureHotelRoom(): BelongsTo
    {
        return $this->belongsTo(DepartureHotelRoom::class, 'departure_hotel_room_id');
    }

    public function departureHotel(): BelongsTo
    {
        return $this->belongsTo(DepartureHotel::class, 'departure_hotel_id');
    }
}
