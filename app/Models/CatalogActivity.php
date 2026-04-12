<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogActivity extends Model
{
    protected $fillable = [
        'wp_post_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'address',
        'type_activity',
        'adult_price',
        'child_price',
        'min_price',
        'duration',
        'max_people',
        'rate_review',
        'is_featured',
        'category',
        'place_text',
        'min_age',
        'max_age',
        'featured_image_wp_id',
        'gallery_image_wp_ids',
        'wp_synced_at',
        'wp_sync_hash',
    ];

    protected $casts = [
        'wp_post_id' => 'integer',
        'adult_price' => 'decimal:2',
        'child_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_people' => 'integer',
        'rate_review' => 'decimal:1',
        'is_featured' => 'boolean',
        'min_age' => 'integer',
        'max_age' => 'integer',
        'featured_image_wp_id' => 'integer',
        'gallery_image_wp_ids' => 'array',
        'wp_synced_at' => 'datetime',
    ];
}
