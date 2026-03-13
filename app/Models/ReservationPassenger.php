<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPassenger extends Model
{
    /**
     * Connexion Laravel principale (et non la connexion WordPress `wp`).
     */
    protected $connection = 'mysql';

    protected $table = 'reservation_passengers';

    protected $fillable = [
        'reservation_id',
        'first_name',
        'last_name',
        'type',
        'birth_date',
        'document_type',
        'document_number',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'birth_date' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}

