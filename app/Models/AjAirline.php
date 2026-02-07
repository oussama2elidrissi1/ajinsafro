<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AjAirline extends Model
{
    protected $connection = 'wp';

    protected $table = 'aj_airlines';

    protected $fillable = [
        'name',
        'iata_code',
        'logo_url',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tourFlights(): HasMany
    {
        return $this->hasMany(TourFlight::class, 'airline_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
