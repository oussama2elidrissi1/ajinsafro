<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartureRoomAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'departure_id',
        'hotel_id',
        'room_type',
        'quantity',
        'capacity_per_room',
        'sort_order',
    ];

    protected $casts = [
        'departure_id' => 'integer',
        'hotel_id' => 'integer',
        'quantity' => 'integer',
        'capacity_per_room' => 'integer',
        'sort_order' => 'integer',
    ];

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }
}
