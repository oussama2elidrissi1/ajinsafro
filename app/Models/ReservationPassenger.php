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
        'gender',
        'traveler_type',
        'birth_date',
        'document_type',
        'document_number',
        'relationship_to_main',
        'nationality',
        'phone',
        'email',
        'traveler_key',
        'is_main',
        'consumes_bed',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'birth_date' => 'date',
        'is_main' => 'boolean',
        'consumes_bed' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
