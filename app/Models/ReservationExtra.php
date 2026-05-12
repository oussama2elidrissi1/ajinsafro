<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationExtra extends Model
{
    protected $connection = 'mysql';

    protected $table = 'reservation_extras';

    protected $fillable = [
        'reservation_id',
        'voyage_extra_id',
        'name',
        'description',
        'price',
        'unit_price',
        'quantity',
        'total_price',
        'application_scope',
        'passenger_key',
        'traveler_keys',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'voyage_extra_id' => 'integer',
        'price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'traveler_keys' => 'array',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
