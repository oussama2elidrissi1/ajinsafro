<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupDealCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function groupDeals(): BelongsToMany
    {
        return $this->belongsToMany(GroupDeal::class, 'group_deal_category_group_deal')->withTimestamps();
    }
}
