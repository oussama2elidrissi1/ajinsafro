<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageCancellationTerm extends Model
{
    protected $fillable = [
        'voyage_id',
        'days_before_departure',
        'refund_percent',
        'is_active',
        'sort_order',
        'note',
    ];

    protected $casts = [
        'refund_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
