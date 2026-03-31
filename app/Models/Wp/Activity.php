<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_activities';

    protected $fillable = [
        'title',
        'activity_type',
        'slug',
        'description',
        'icon',
        'image_id',
        'gallery_image_ids',
        'base_price',
        'adult_price',
        'child_price',
        'min_age',
        'max_age',
        'default_duration_minutes',
        'location_text',
        'region_name',
        'is_active',
    ];

    protected $casts = [
        'image_id' => 'integer',
        'gallery_image_ids' => 'array',
        'default_duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'adult_price' => 'decimal:2',
        'child_price' => 'decimal:2',
        'min_age' => 'integer',
        'max_age' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dayActivities()
    {
        return $this->hasMany(TourDayActivity::class, 'activity_id');
    }
}
