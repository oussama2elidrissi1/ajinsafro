<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityOffer extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'country',
        'city',
        'category',
        'duration_label',
        'badge',
        'short_description',
        'includes',
        'image_url',
        'price_from',
        'currency',
        'availability_label',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'includes' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'price_from' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ActivityOffer $offer): void {
            $baseSlug = Str::slug((string) ($offer->slug ?: $offer->title));
            if ($baseSlug === '') {
                $baseSlug = 'activite-ajinsafro';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $slug)
                ->when($offer->exists, fn ($query) => $query->whereKeyNot($offer->getKey()))
                ->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $offer->slug = $slug;
        });
    }
}
