<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StActivity extends Model
{
    protected $table = 'cFdgeZ_st_activity';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'multi_location',
        'id_location',
        'address',
        'price',
        'sale_price',
        'child_price',
        'adult_price',
        'infant_price',
        'min_price',
        'type_activity',
        'check_in',
        'check_out',
        'rate_review',
        'activity_booking_period',
        'max_people',
        'duration',
        'is_sale_schedule',
        'discount',
        'sale_price_from',
        'sale_price_to',
        'is_featured',
        'discount_type',
    ];

    public function post()
    {
        return $this->belongsTo(WpPost::class, 'post_id', 'ID');
    }
}
