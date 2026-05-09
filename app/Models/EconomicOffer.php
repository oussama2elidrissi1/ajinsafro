<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EconomicOffer extends Model
{
    public const TYPE_TRAVEL = 'travel';
    public const TYPE_OMRA = 'omra';
    public const TYPE_ACCOMMODATION = 'accommodation';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_PACK = 'pack';

    public const TYPES = [
        self::TYPE_TRAVEL,
        self::TYPE_OMRA,
        self::TYPE_ACCOMMODATION,
        self::TYPE_ACTIVITY,
        self::TYPE_PACK,
    ];

    public const CATEGORY_ECONOMIC = 'economic';
    public const CATEGORY_LAST_MINUTE = 'last_minute';
    public const CATEGORY_PROMOTION = 'promotion';
    public const CATEGORY_FAMILY = 'family';
    public const CATEGORY_GROUP = 'group';

    public const CATEGORIES = [
        self::CATEGORY_ECONOMIC,
        self::CATEGORY_LAST_MINUTE,
        self::CATEGORY_PROMOTION,
        self::CATEGORY_FAMILY,
        self::CATEGORY_GROUP,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FULL = 'full';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_FULL,
        self::STATUS_EXPIRED,
    ];

    public const AVAILABILITY_AVAILABLE = 'available';
    public const AVAILABILITY_LIMITED = 'limited';
    public const AVAILABILITY_FULL = 'full';
    public const AVAILABILITY_EXPIRED = 'expired';

    public const AVAILABILITY_STATUSES = [
        self::AVAILABILITY_AVAILABLE,
        self::AVAILABILITY_LIMITED,
        self::AVAILABILITY_FULL,
        self::AVAILABILITY_EXPIRED,
    ];

    protected $fillable = [
        'title',
        'slug',
        'internal_reference',
        'offer_type',
        'category',
        'status',
        'availability_status',
        'main_image',
        'fallback_image',
        'video_url',
        'short_description',
        'description',
        'price_from',
        'old_price',
        'currency',
        'price_type',
        'deposit_amount',
        'payment_conditions',
        'included_items',
        'excluded_items',
        'departure_date',
        'return_date',
        'duration_days',
        'duration_nights',
        'total_places',
        'available_places',
        'reserved_places',
        'departure_city',
        'destination',
        'country',
        'arrival_city',
        'address_zone',
        'key_distance',
        'transport_included',
        'flight_included',
        'hotel_included',
        'meals_included',
        'guide_included',
        'insurance_included',
        'transfer_included',
        'accommodation_type',
        'hotel_name',
        'hotel_category',
        'room_type',
        'meal_plan',
        'program_summary',
        'cancellation_conditions',
        'required_documents',
        'meta_title',
        'meta_description',
        'seo_image',
        'seo_keywords',
        'sort_order',
        'is_featured',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'old_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'departure_date' => 'date',
        'return_date' => 'date',
        'duration_days' => 'integer',
        'duration_nights' => 'integer',
        'total_places' => 'integer',
        'available_places' => 'integer',
        'reserved_places' => 'integer',
        'included_items' => 'array',
        'excluded_items' => 'array',
        'seo_keywords' => 'array',
        'transport_included' => 'boolean',
        'flight_included' => 'boolean',
        'hotel_included' => 'boolean',
        'meals_included' => 'boolean',
        'guide_included' => 'boolean',
        'insurance_included' => 'boolean',
        'transfer_included' => 'boolean',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'type_label',
        'category_label',
        'status_label',
        'availability_label',
        'main_image_url',
        'fallback_image_url',
        'seo_image_url',
        'price_from_value',
        'remaining_places',
        'duration_label',
        'is_promoted',
    ];

    protected static function booted(): void
    {
        static::saving(function (EconomicOffer $offer): void {
            $baseSlug = Str::slug((string) ($offer->slug ?: $offer->title));
            if ($baseSlug === '') {
                $baseSlug = 'formule-economique';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $slug)
                ->when($offer->exists, fn (Builder $query) => $query->whereKeyNot($offer->getKey()))
                ->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $offer->slug = $slug;

            if ($offer->status === self::STATUS_PUBLISHED && $offer->published_at === null) {
                $offer->published_at = now();
            }

            if ($offer->status !== self::STATUS_PUBLISHED) {
                $offer->published_at = null;
            }

            $offer->availability_status = $offer->computeAvailabilityStatus();
            $offer->is_active = $offer->computeIsActive();
        });
    }

    public function images(): HasMany
    {
        return $this->hasMany(EconomicOfferImage::class, 'offer_id')->orderBy('sort_order')->orderBy('id');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(EconomicOfferDeparture::class, 'offer_id')->orderBy('departure_date')->orderBy('id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(EconomicOfferPrice::class, 'offer_id')->orderBy('sort_order')->orderBy('id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(EconomicOfferRequest::class, 'offer_id')->orderByDesc('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereIn('availability_status', [
            self::AVAILABILITY_AVAILABLE,
            self::AVAILABILITY_LIMITED,
        ]);
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        $type = trim((string) $type);

        if ($type === '' || ! in_array($type, self::TYPES, true)) {
            return $query;
        }

        return $query->where('offer_type', $type);
    }

    public function scopeLowBudget(Builder $query, ?float $maxBudget = null): Builder
    {
        if ($maxBudget === null || $maxBudget <= 0) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($maxBudget) {
            $builder
                ->whereNotNull('price_from')
                ->where('price_from', '<=', $maxBudget)
                ->orWhereHas('departures', fn (Builder $departureQuery) => $departureQuery->whereNotNull('price_from')->where('price_from', '<=', $maxBudget))
                ->orWhereHas('prices', fn (Builder $priceQuery) => $priceQuery->where('price', '<=', $maxBudget));
        });
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_TRAVEL => 'Voyage',
            self::TYPE_OMRA => 'Omra',
            self::TYPE_ACCOMMODATION => 'Hebergement',
            self::TYPE_ACTIVITY => 'Activite',
            self::TYPE_PACK => 'Pack',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_ECONOMIC => 'Economique',
            self::CATEGORY_LAST_MINUTE => 'Derniere minute',
            self::CATEGORY_PROMOTION => 'Promotion',
            self::CATEGORY_FAMILY => 'Famille',
            self::CATEGORY_GROUP => 'Groupe',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publie',
            self::STATUS_FULL => 'Complet',
            self::STATUS_EXPIRED => 'Expire',
        ];
    }

    public static function availabilityOptions(): array
    {
        return [
            self::AVAILABILITY_AVAILABLE => 'Disponible',
            self::AVAILABILITY_LIMITED => 'Places limitees',
            self::AVAILABILITY_FULL => 'Complet',
            self::AVAILABILITY_EXPIRED => 'Offre expiree',
        ];
    }

    public static function priceTypeOptions(): array
    {
        return [
            'per_person' => 'Par personne',
            'per_room' => 'Par chambre',
            'per_group' => 'Par groupe',
            'per_night' => 'Par nuit',
        ];
    }

    public static function mealPlanOptions(): array
    {
        return [
            'none' => 'Sans repas',
            'breakfast' => 'Petit dejeuner',
            'half_board' => 'Demi-pension',
            'full_board' => 'Pension complete',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return static::typeOptions()[$this->offer_type] ?? Str::title(str_replace('_', ' ', (string) $this->offer_type));
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::categoryOptions()[$this->category] ?? Str::title(str_replace('_', ' ', (string) $this->category));
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? Str::title(str_replace('_', ' ', (string) $this->status));
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return static::availabilityOptions()[$this->availability_status] ?? Str::title(str_replace('_', ' ', (string) $this->availability_status));
    }

    public function getMainImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->main_image);
    }

    public function getFallbackImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->fallback_image);
    }

    public function getSeoImageUrlAttribute(): ?string
    {
        return $this->storageUrl($this->seo_image);
    }

    public function getPriceFromValueAttribute(): ?float
    {
        $departurePrice = $this->relationLoaded('departures')
            ? $this->departures->pluck('price_from')->filter(fn ($value) => $value !== null)->min()
            : $this->departures()->whereNotNull('price_from')->min('price_from');

        $variablePrice = $this->relationLoaded('prices')
            ? $this->prices->pluck('price')->filter(fn ($value) => $value !== null)->min()
            : $this->prices()->min('price');

        $candidates = array_filter([
            $this->price_from !== null ? (float) $this->price_from : null,
            $departurePrice !== null ? (float) $departurePrice : null,
            $variablePrice !== null ? (float) $variablePrice : null,
        ], static fn ($value) => $value !== null);

        return $candidates === [] ? null : (float) min($candidates);
    }

    public function getRemainingPlacesAttribute(): int
    {
        $upcomingDeparture = $this->resolveUpcomingDeparture();

        if ($upcomingDeparture !== null) {
            return $upcomingDeparture->remaining_places;
        }

        return max(0, (int) $this->available_places - (int) $this->reserved_places);
    }

    public function getDurationLabelAttribute(): ?string
    {
        $days = (int) ($this->duration_days ?? 0);
        $nights = (int) ($this->duration_nights ?? 0);

        if ($days <= 0 && $nights <= 0) {
            return null;
        }

        if ($days > 0 && $nights > 0) {
            return sprintf('%d jours / %d nuits', $days, $nights);
        }

        return $days > 0 ? sprintf('%d jours', $days) : sprintf('%d nuits', $nights);
    }

    public function getIsPromotedAttribute(): bool
    {
        return $this->old_price !== null && $this->price_from_value !== null && (float) $this->old_price > (float) $this->price_from_value;
    }

    public function resolveUpcomingDeparture(): ?EconomicOfferDeparture
    {
        $today = now()->toDateString();

        if ($this->relationLoaded('departures')) {
            return $this->departures
                ->filter(fn (EconomicOfferDeparture $departure) => $departure->departure_date?->toDateString() >= $today)
                ->sortBy('departure_date')
                ->first();
        }

        return $this->departures()
            ->whereDate('departure_date', '>=', $today)
            ->orderBy('departure_date')
            ->first();
    }

    public function computeAvailabilityStatus(): string
    {
        if ($this->status === self::STATUS_EXPIRED || $this->isDateExpired()) {
            return self::AVAILABILITY_EXPIRED;
        }

        $remaining = $this->remaining_places;

        if ($this->status === self::STATUS_FULL || $remaining <= 0) {
            return self::AVAILABILITY_FULL;
        }

        if ($remaining <= 5) {
            return self::AVAILABILITY_LIMITED;
        }

        return self::AVAILABILITY_AVAILABLE;
    }

    public function computeIsActive(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ! $this->isDateExpired()
            && $this->remaining_places > 0;
    }

    private function isDateExpired(): bool
    {
        $today = now()->toDateString();

        if ($this->relationLoaded('departures') && $this->departures->isNotEmpty()) {
            return $this->departures
                ->filter(fn (EconomicOfferDeparture $departure) => $departure->departure_date !== null)
                ->every(fn (EconomicOfferDeparture $departure) => $departure->departure_date?->toDateString() < $today);
        }

        if ($this->return_date !== null) {
            return $this->return_date->toDateString() < $today;
        }

        if ($this->departure_date !== null) {
            return $this->departure_date->toDateString() < $today;
        }

        return false;
    }

    private function storageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
