<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StCar extends Model
{
    protected $table = 'cFdgeZ_st_cars';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'multi_location',
        'id_location',
        'cars_address',
        'cars_price',
        'sale_price',
        'number_car',
        'cars_booking_period',
        'is_sale_schedule',
        'discount',
        'sale_price_from',
        'sale_price_to',
        'min_price',
        'max_price',
        'is_featured',
    ];

    public function post()
    {
        return $this->belongsTo(WpPost::class, 'post_id', 'ID');
    }
}
