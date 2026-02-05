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
        'wp_post_id', 'name', 'slug', 'description', 'accroche', 'destination', 'duration_text',
        'price_from', 'old_price', 'currency', 'min_people', 'max_people', 'departure_policy', 'status',
        'featured_image', 'wp_synced_at', 'wp_sync_hash', 'wp_last_modified_gmt_cache',
        // Traveler metas
        'tour_price_by', 'is_featured', 'st_google_map', 'multi_location',
        'discount_by_people_type', 'discount_type', 'calculator_discount_by_people_type',
        'hide_adult_in_booking_form', 'st_tour_external_booking',
        'tours_include', 'tours_exclude', 'tours_highlight', 'tours_program_style',
        'payment_gateway_metas', 'gallery_wp_ids',
    ];

    protected $casts = [
        'wp_post_id' => 'integer',
        'price_from' => 'integer',
        'old_price' => 'integer',
        'min_people' => 'integer',
        'max_people' => 'integer',
        'wp_synced_at' => 'datetime',
        'wp_last_modified_gmt_cache' => 'datetime',
        'is_featured' => 'boolean',
        'hide_adult_in_booking_form' => 'boolean',
        'tours_include' => 'array',
        'tours_exclude' => 'array',
        'tours_highlight' => 'array',
        'payment_gateway_metas' => 'array',
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

    public function dayItems()
    {
        return $this->hasMany(TravelDayItem::class)->orderBy('day_number')->orderBy('sort_order');
    }

    public function packageSessions()
    {
        return $this->hasMany(PackageSession::class);
    }

    public function checkoutTokens()
    {
        return $this->hasMany(CheckoutToken::class);
    }

    /**
     * Public URL for the featured image (public disk).
     * Falls back to first gallery image if featured_image is null.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        // If featured_image is set, use it
        if (!empty($this->featured_image)) {
            if (str_starts_with($this->featured_image, 'http://')
                || str_starts_with($this->featured_image, 'https://')
                || str_starts_with($this->featured_image, 'data:')) {
                return $this->featured_image;
            }
            return Storage::disk('public')->url($this->featured_image);
        }
        
        // Fallback to first gallery image
        $firstImage = $this->images()->orderBy('sort_order')->first();
        if ($firstImage) {
            return $firstImage->url;
        }
        
        return null;
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
