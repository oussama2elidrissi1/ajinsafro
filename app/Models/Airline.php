<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airline extends Model
{
    protected $fillable = [
        'name',
        'code_iata',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function voyageFlights(): HasMany
    {
        return $this->hasMany(VoyageFlight::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
