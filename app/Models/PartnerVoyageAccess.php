<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerVoyageAccess extends Model
{
    protected $table = 'partner_voyage_access';

    protected $fillable = ['partner_id', 'voyage_id'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}
