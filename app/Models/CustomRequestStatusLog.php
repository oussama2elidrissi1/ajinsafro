<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRequestStatusLog extends Model
{
    protected $fillable = ['custom_request_id', 'user_id', 'old_status', 'new_status', 'note'];

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
