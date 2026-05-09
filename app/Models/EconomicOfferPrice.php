<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicOfferPrice extends Model
{
    protected $fillable = [
        'offer_id',
        'label',
        'type',
        'price',
        'old_price',
        'stock',
        'condition',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'stock' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'is_promoted',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(EconomicOffer::class, 'offer_id');
    }

    public function getIsPromotedAttribute(): bool
    {
        return $this->old_price !== null && (float) $this->old_price > (float) $this->price;
    }
}
