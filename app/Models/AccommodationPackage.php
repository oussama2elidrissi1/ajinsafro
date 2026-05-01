<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccommodationPackage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'country',
        'city',
        'duration_days',
        'nights',
        'pension_type',
        'accommodation_type',
        'badge',
        'short_description',
        'includes',
        'image_url',
        'price_from',
        'currency',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'includes' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'price_from' => 'decimal:2',
        'duration_days' => 'integer',
        'nights' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (AccommodationPackage $package): void {
            $baseSlug = Str::slug((string) ($package->slug ?: $package->title));
            if ($baseSlug === '') {
                $baseSlug = 'pack-hebergement';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $slug)
                ->when($package->exists, fn ($query) => $query->whereKeyNot($package->getKey()))
                ->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $package->slug = $slug;
        });
    }
}
