<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageDeparturePlace extends Model
{
    protected $table = 'voyage_departure_places';

    protected $fillable = [
        'voyage_id',
        'name',
        'code',
        'is_active',
        'sort_order',
        'price',
    ];

    protected $casts = [
        'voyage_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
