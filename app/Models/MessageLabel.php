<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageLabel extends Model
{
    protected $table = 'message_labels';

    protected $fillable = ['name', 'color'];

    public function messages(): HasMany
    {
        return $this->hasMany(ReservationMessage::class, 'label_id');
    }
}
