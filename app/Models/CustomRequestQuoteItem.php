<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRequestQuoteItem extends Model
{
    protected $fillable = [
        'custom_request_quote_id',
        'custom_request_quote_day_id',
        'service_type',
        'title',
        'description',
        'supplier_name',
        'quantity',
        'unit_purchase_price',
        'margin_type',
        'margin_value',
        'unit_margin',
        'unit_sale_price',
        'total_purchase',
        'total_margin',
        'total_sale',
        'is_optional',
        'data_json',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_purchase_price' => 'decimal:2',
        'margin_value' => 'decimal:2',
        'unit_margin' => 'decimal:2',
        'unit_sale_price' => 'decimal:2',
        'total_purchase' => 'decimal:2',
        'total_margin' => 'decimal:2',
        'total_sale' => 'decimal:2',
        'is_optional' => 'boolean',
        'data_json' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomRequestQuote::class, 'custom_request_quote_id');
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(CustomRequestQuoteDay::class, 'custom_request_quote_day_id');
    }
}
