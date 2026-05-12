<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationDocument extends Model
{
    protected $table = 'reservation_documents';

    protected $fillable = [
        'reservation_id',
        'type',
        'title',
        'file_path',
        'mime_type',
        'created_by',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
