<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'is_group_deal',
        'discount_by_people_type', 'discount_type', 'calculator_discount_by_people_type',
        'hide_adult_in_booking_form', 'st_tour_external_booking',
        'tours_include', 'tours_exclude', 'tours_highlight', 'tours_program_style',
        'payment_gateway_metas', 'gallery_wp_ids', 'logistics_meta',
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
        'is_group_deal' => 'boolean',
        'hide_adult_in_booking_form' => 'boolean',
        'tours_include' => 'array',
        'tours_exclude' => 'array',
        'tours_highlight' => 'array',
        'payment_gateway_metas' => 'array',
        'logistics_meta' => 'array',
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

    /** Partenaires revendeurs ayant accès à ce voyage (vide = tous). */
    public function partnerAccess(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_voyage_access', 'voyage_id', 'partner_id')->withTimestamps();
    }

    /** Thèmes catalogue (Laravel) — synchronisés vers WP `tours_cat` pour le site public. */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(VoyageTheme::class, 'voyage_voyage_theme')->withTimestamps();
    }

    public function discountRules()
    {
        return $this->hasMany(VoyageDiscountRule::class)->orderBy('sort_order')->orderBy('priority');
    }

    public function cancellationTerms()
    {
        return $this->hasMany(VoyageCancellationTerm::class)->orderBy('sort_order');
    }

    public function flights()
    {
        return $this->hasMany(VoyageFlight::class)->orderBy('direction');
    }

    /** Vol aller — toujours attaché au Jour 1 du programme. */
    public function outboundFlight()
    {
        return $this->hasOne(VoyageFlight::class)->where('direction', 'outbound');
    }

    /** Vol retour — toujours attaché au dernier jour du programme (Jour N). */
    public function inboundFlight()
    {
        return $this->hasOne(VoyageFlight::class)->where('direction', 'inbound');
    }

    /** Options de vols multiples (aller / retour / segments par jour). */
    public function flightOptions()
    {
        return $this->hasMany(VoyageFlightOption::class)->orderBy('type')->orderBy('sort_order')->orderBy('id');
    }

    /** Extras réservation (workspace / catalogue), configurables dans le CRUD voyage. */
    public function extras()
    {
        return $this->hasMany(VoyageExtra::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Paliers de prix pour le Group Deal. */
    public function pricingTiers()
    {
        return $this->hasMany(GroupDealPricingTier::class)->orderBy('min_participants');
    }

    /**
     * Retourne le palier actif selon le nombre de participants.
     * Le palier ayant le min_participants le plus élevé encore <= $count.
     */
    public function activePricingTier(int $count): ?GroupDealPricingTier
    {
        return $this->pricingTiers()
            ->where('min_participants', '<=', $count)
            ->where(function ($query) use ($count) {
                $query->whereNull('max_people')
                    ->orWhere('max_people', '>=', $count);
            })
            ->orderBy('min_participants', 'desc')
            ->first();
    }

    /**
     * Public URL for the featured image (public disk).
     * Falls back to first gallery image if featured_image is null.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        // If featured_image is set, use it
        if (! empty($this->featured_image)) {
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
        if (! $this->old_price || $this->old_price <= 0 || ! $this->price_from) {
            return null;
        }
        if ($this->price_from >= $this->old_price) {
            return 0;
        }

        return (int) round((($this->old_price - $this->price_from) / $this->old_price) * 100);
    }

    public function getDiscountAmountAttribute(): ?int
    {
        if (! $this->old_price || ! $this->price_from) {
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

    /**
     * Tous les ids Laravel {@see Voyage} qui représentent le même circuit WordPress (même wp_post_id).
     * Sert à aligner listes, stats et catalogue quand plusieurs lignes voyages pointent vers un même tour WP.
     *
     * @return list<int>
     */
    public static function allIdsSharingWpTour(int $voyageId): array
    {
        $row = static::query()->find($voyageId);
        if (! $row) {
            return [$voyageId];
        }
        $wp = $row->wp_post_id;
        if ($wp === null || (int) $wp <= 0) {
            return [(int) $row->id];
        }

        return static::query()
            ->where('wp_post_id', (int) $wp)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Id voyage « canonique » pour un tour WordPress : le plus petit {@see Voyage::id} (même règle que le catalogue workspace).
     */
    public static function canonicalVoyageId(int $voyageId): int
    {
        $ids = static::allIdsSharingWpTour($voyageId);

        return $ids[0] ?? $voyageId;
    }
}
