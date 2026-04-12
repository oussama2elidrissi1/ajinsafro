<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogTransfer extends Model
{
    protected $fillable = [
        'wp_post_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'cars_address',
        'cars_price',
        'min_price',
        'max_price',
        'number_car',
        'is_featured',
        'transfer_from',
        'transfer_to',
        'transfer_type',
        'transfer_capacity',
        'transfer_vehicle_type',
        'featured_image_wp_id',
        'wp_synced_at',
        'wp_sync_hash',
    ];

    protected $casts = [
        'wp_post_id' => 'integer',
        'cars_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'number_car' => 'integer',
        'is_featured' => 'boolean',
        'transfer_capacity' => 'integer',
        'featured_image_wp_id' => 'integer',
        'wp_synced_at' => 'datetime',
    ];
}
