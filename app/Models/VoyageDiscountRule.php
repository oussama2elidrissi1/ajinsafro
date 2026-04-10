<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageDiscountRule extends Model
{
    protected $fillable = [
        'voyage_id',
        'reduction_type',
        'scope',
        'condition_type',
        'condition_json',
        'value',
        'priority',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'condition_json' => 'array',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
