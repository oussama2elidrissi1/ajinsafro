<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'main_image_path',
        'rating_average',
        'reviews_count',
        'is_active',
        'amenities_summary',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating_average' => 'integer',
        'reviews_count' => 'integer',
        'is_active' => 'boolean',
        'amenities_summary' => 'array',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(HotelImage::class)->orderBy('position')->orderBy('id');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(HotelRoomType::class)->orderBy('position')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(HotelReview::class)->where('is_visible', true)->orderByDesc('created_at');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(HotelAmenity::class, 'hotel_amenity_hotel')
            ->withTimestamps()
            ->orderBy('label');
    }
}
