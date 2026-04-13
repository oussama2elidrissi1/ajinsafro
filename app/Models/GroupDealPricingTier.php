<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDealPricingTier extends Model
{
    protected $fillable = [
        'voyage_id',
        'min_participants',
        'price_per_person',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'min_participants' => 'integer',
        'price_per_person' => 'decimal:2',
        'sort_order'       => 'integer',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
