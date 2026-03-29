<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageExtra extends Model
{
    protected $fillable = [
        'voyage_id',
        'name',
        'description',
        'price_adult',
        'price_child',
        'is_active',
        'sort_order',
        'extra_type',
        'icon',
    ];

    protected $casts = [
        'voyage_id' => 'integer',
        'price_adult' => 'decimal:2',
        'price_child' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
