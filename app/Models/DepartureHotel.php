<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartureHotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'departure_id',
        'hotel_id',
        'hotel_name',
        'stars',
        'address',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'departure_id' => 'integer',
        'hotel_id' => 'integer',
        'stars' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(DepartureHotelRoom::class)->orderBy('id');
    }
}

