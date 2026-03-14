<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourHotelRoom extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_hotel_rooms';

    protected $fillable = [
        'tour_hotel_id',
        'room_type',
        'room_label',
        'room_code',
        'room_count',
        'capacity_adults',
        'capacity_children',
        'capacity_total',
        'supplement',
        'description',
        'is_active',
        'sort_order',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'room_count' => 'integer',
        'capacity_adults' => 'integer',
        'capacity_children' => 'integer',
        'capacity_total' => 'integer',
        'supplement' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tourHotel(): BelongsTo
    {
        return $this->belongsTo(TourHotel::class, 'tour_hotel_id');
    }
}
