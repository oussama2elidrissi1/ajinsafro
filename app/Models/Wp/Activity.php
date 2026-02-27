<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_activities';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon',
        'image_id',
        'base_price',
        'default_duration_minutes',
        'location_text',
        'is_active',
    ];

    protected $casts = [
        'default_duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function dayActivities()
    {
        return $this->hasMany(TourDayActivity::class, 'activity_id');
    }
}
