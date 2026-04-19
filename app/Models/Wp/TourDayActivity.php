<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;

class TourDayActivity extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_day_activities';

    protected $fillable = [
        'tour_id',
        'day_id',
        'activity_id',
        'sort_order',
        'is_included',
        'day_scope',
        'is_mandatory',
        'is_editable',
        'custom_title',
        'custom_description',
        'custom_price',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_included' => 'integer',
        'is_mandatory' => 'integer',
        'is_editable' => 'integer',
        'custom_price' => 'decimal:2',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function tourDay()
    {
        return $this->belongsTo(TourDay::class, 'day_id');
    }
}
