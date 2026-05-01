<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDealPricingTier extends Model
{
    protected $fillable = [
        'group_deal_id',
        'voyage_id',
        'min_participants',
        'min_people',
        'max_people',
        'price_per_person',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'group_deal_id' => 'integer',
        'voyage_id' => 'integer',
        'min_participants' => 'integer',
        'min_people' => 'integer',
        'max_people' => 'integer',
        'price_per_person' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (GroupDealPricingTier $tier): void {
            $value = $tier->min_people ?? $tier->min_participants;
            $tier->min_people = $value;
            $tier->min_participants = $value;
        });
    }

    public function groupDeal(): BelongsTo
    {
        return $this->belongsTo(GroupDeal::class);
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
