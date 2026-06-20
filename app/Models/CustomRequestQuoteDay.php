<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomRequestQuoteDay extends Model
{
    protected $fillable = [
        'custom_request_quote_id',
        'day_number',
        'date',
        'title',
        'city',
        'client_description',
        'internal_notes',
        'sort_order',
    ];

    protected $casts = [
        'date' => 'date',
        'day_number' => 'integer',
        'sort_order' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomRequestQuote::class, 'custom_request_quote_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(CustomRequestQuoteItem::class, 'custom_request_quote_day_id')->orderBy('sort_order')->orderBy('id');
    }
}
