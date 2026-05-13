<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReservationRoomAllocation extends Model
{
    protected $table = 'reservation_room_allocations';

    protected $fillable = [
        'reservation_id',
        'reservation_dossier_id',
        'travel_date_id',
        'tour_hotel_id',
        'tour_hotel_room_id',
        'seats_allocated',
        'rooms_new_count',
        'rooms_total_count',
        'room_source_type',
        'room_source_id',
        'room_type',
        'occupancy_mode',
        'capacity',
        'occupied_count',
        'status',
        'supplement_total',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'reservation_dossier_id' => 'integer',
        'travel_date_id' => 'integer',
        'tour_hotel_id' => 'integer',
        'tour_hotel_room_id' => 'integer',
        'seats_allocated' => 'integer',
        'rooms_new_count' => 'integer',
        'rooms_total_count' => 'integer',
        'room_source_id' => 'integer',
        'capacity' => 'integer',
        'occupied_count' => 'integer',
        'supplement_total' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(ReservationDossier::class, 'reservation_dossier_id');
    }

    public function travelers(): BelongsToMany
    {
        return $this->belongsToMany(
            ReservationPassenger::class,
            'reservation_room_allocation_travelers',
            'room_allocation_id',
            'traveler_id'
        )->withTimestamps();
    }
}
