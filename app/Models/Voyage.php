<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Voyage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'accroche', 'destination', 'duration_text',
        'price_from', 'old_price', 'currency', 'min_people', 'departure_policy', 'status',
        'featured_image',
    ];

    protected $casts = [
        'price_from' => 'integer',
        'old_price' => 'integer',
        'min_people' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Voyage $voyage) {
            if (empty($voyage->slug)) {
                $voyage->slug = Str::slug($voyage->name);
            }
        });
        static::deleting(function (Voyage $voyage) {
            if ($voyage->featured_image) {
                Storage::disk('public')->delete($voyage->featured_image);
            }
            foreach ($voyage->images as $img) {
                Storage::disk('public')->delete($img->path);
            }
        });
    }

    public function programDays()
    {
        return $this->hasMany(TravelProgramDay::class)->orderBy('day_number');
    }

    public function departures()
    {
        return $this->hasMany(Departure::class)->orderBy('start_date');
    }

    public function images()
    {
        return $this->hasMany(VoyageImage::class)->orderBy('sort_order');
    }

    /**
     * Public URL for the featured image (public disk).
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }
        return Storage::disk('public')->url($this->featured_image);
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (!$this->old_price || $this->old_price <= 0 || !$this->price_from) {
            return null;
        }
        if ($this->price_from >= $this->old_price) {
            return 0;
        }
        return (int) round((($this->old_price - $this->price_from) / $this->old_price) * 100);
    }

    public function getDiscountAmountAttribute(): ?int
    {
        if (!$this->old_price || !$this->price_from) {
            return null;
        }
        $diff = $this->old_price - $this->price_from;
        return $diff > 0 ? $diff : 0;
    }

    public function getCurrencySymbolAttribute(): string
    {
        return match (strtoupper($this->currency ?? 'MAD')) {
            'MAD' => 'DH',
            'EUR' => '€',
            'USD' => '$',
            default => $this->currency ?? 'DH',
        };
    }
}
