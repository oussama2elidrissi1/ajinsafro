<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRequestService extends Model
{
    protected $fillable = ['custom_request_id', 'service_key', 'service_label'];

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
    }
}
