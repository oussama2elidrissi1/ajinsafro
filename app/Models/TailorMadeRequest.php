<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TailorMadeRequest extends Model
{
    protected $table = 'tailor_made_requests';

    protected $fillable = [
        'type',
        'source',
        'status',
        'voyage_id',
        'wp_post_id',
        'tour_title',
        'tour_url',
        'booking_url',
        'custom_departure_place',
        'custom_departure_date',
        'adults',
        'children',
        'travellers_total',
        'price_currency',
        'price_per_person',
        'price_total',
        'client_first_name',
        'client_last_name',
        'client_phone',
        'client_email',
        'message',
        'meta',
    ];

    protected $casts = [
        'custom_departure_date' => 'date',
        'meta' => 'array',
        'price_per_person' => 'decimal:2',
        'price_total' => 'decimal:2',
    ];
}

