<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDealServiceItem extends Model
{
    public const TYPE_INCLUDED = 'included';
    public const TYPE_NOT_INCLUDED = 'not_included';

    protected $table = 'group_deal_services';

    protected $fillable = [
        'group_deal_id',
        'name',
        'type',
        'sort_order',
    ];

    protected $casts = [
        'group_deal_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function groupDeal(): BelongsTo
    {
        return $this->belongsTo(GroupDeal::class);
    }
}
