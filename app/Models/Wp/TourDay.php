<?php

namespace App\Models\Wp;

use Illuminate\Database\Eloquent\Model;

class TourDay extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_tour_days';

    protected $fillable = [
        'tour_id',
        'day_number',
        'title',
        'description',
        'meals',
        'accommodation',
        'image_url',
        'mode',
        'day_title',
        'notes',
    ];

    protected $casts = [
        'day_number' => 'integer',
    ];

    public function tour()
    {
        return $this->belongsTo(WpPost::class, 'tour_id', 'ID');
    }

    public function dayActivities()
    {
        return $this->hasMany(TourDayActivity::class, 'day_id')->orderBy('sort_order');
    }
}
