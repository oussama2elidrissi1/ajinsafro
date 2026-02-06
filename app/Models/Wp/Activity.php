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
        'default_duration_minutes',
        'location_text',
    ];

    protected $casts = [
        'default_duration_minutes' => 'integer',
    ];

    public function dayActivities()
    {
        return $this->hasMany(TourDayActivity::class, 'activity_id');
    }
}
