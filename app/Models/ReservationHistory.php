<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationHistory extends Model
{
    protected $table = 'reservation_histories';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'note',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
