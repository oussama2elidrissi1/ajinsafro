<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityOffer extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'country',
        'city',
        'category',
        'duration_label',
        'badge',
        'short_description',
        'includes',
        'image_url',
        'price_from',
        'currency',
        'availability_label',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'includes' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'price_from' => 'decimal:2',
        'sort_order' => 'integer',
    ];
}
