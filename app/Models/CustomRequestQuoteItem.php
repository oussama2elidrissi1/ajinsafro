<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRequestQuoteItem extends Model
{
    protected $fillable = [
        'custom_request_quote_id', 'service_type', 'description', 'supplier_name', 'quantity',
        'unit_purchase_price', 'unit_margin', 'unit_sale_price', 'total_purchase',
        'total_margin', 'total_sale', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_purchase_price' => 'decimal:2',
        'unit_margin' => 'decimal:2',
        'unit_sale_price' => 'decimal:2',
        'total_purchase' => 'decimal:2',
        'total_margin' => 'decimal:2',
        'total_sale' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomRequestQuote::class, 'custom_request_quote_id');
    }
}
