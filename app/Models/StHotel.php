<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StHotel extends Model
{
    protected $table = 'cFdgeZ_st_hotel';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'multi_location',
        'id_location',
        'address',
        'allow_full_day',
        'rate_review',
        'hotel_star',
        'price_avg',
        'min_price',
        'hotel_booking_period',
        'map_lat',
        'map_lng',
        'is_sale_schedule',
        'post_origin',
        'is_featured',
    ];

    public function post()
    {
        return $this->belongsTo(WpPost::class, 'post_id', 'ID');
    }
}
