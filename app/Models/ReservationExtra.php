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
        'name',
        'price',
        'passenger_key',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'price' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
