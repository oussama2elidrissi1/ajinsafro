<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageStar extends Model
{
    protected $table = 'message_stars';

    protected $fillable = ['user_id', 'message_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ReservationMessage::class, 'message_id');
    }
}
