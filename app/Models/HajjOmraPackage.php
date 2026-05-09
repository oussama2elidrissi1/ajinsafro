<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HajjOmraPackage extends Model
{
    public const TYPE_OMRA = 'omra';
    public const TYPE_HAJJ = 'hajj';
    public const TYPE_RAMADAN = 'ramadan';
    public const TYPE_LOW_COST = 'low_cost';
    public const TYPE_PREMIUM = 'premium';

    public const TYPES = [
        self::TYPE_OMRA,
        self::TYPE_HAJJ,
        self::TYPE_RAMADAN,
        self::TYPE_LOW_COST,
        self::TYPE_PREMIUM,
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FULL = 'full';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_EXPIRED,
        self::STATUS_FULL,
    ];

    protected $fillable = [
        'title',
        'slug',
        'type',
        'status',
        'main_image',
        'short_description',
        'description',
        'departure_city',
        'destination',
        'duration_days',
        'duration_nights',
        'start_date',
        'return_date',
        'adult_price',
        'child_price',
        'baby_price',
        'currency',
        'available_places',
        'reserved_places',
        'makkah_hotel',
        'makkah_haram_distance',
        'madinah_hotel',
        'madinah_haram_distance',
        'room_type',
        'transport_included',
        'visa_included',
        'guidance_included',
        'meal_plan',
        'included_items',
        'excluded_items',
        'booking_conditions',
        'required_documents',
        'meta_title',
        'meta_description',
        'sort_order',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'return_date' => 'date',
        'adult_price' => 'decimal:2',
        'child_price' => 'decimal:2',
        'baby_price' => 'decimal:2',
        'available_places' => 'integer',
        'reserved_places' => 'integer',
        'transport_included' => 'boolean',
        'visa_included' => 'boolean',
        'guidance_included' => 'boolean',
        'included_items' => 'array',
        'excluded_items' => 'array',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'main_image_url',
        'type_label',
        'status_label',
        'price_from_value',
        'remaining_places',
        'duration_label',
    ];

    protected static function booted(): void
    {
        static::saving(function (HajjOmraPackage $package): void {
            $baseSlug = Str::slug((string) ($package->slug ?: $package->title));
            if ($baseSlug === '') {
                $baseSlug = 'hajj-omra-offer';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (static::query()
                ->where('slug', $slug)
                ->when($package->exists, fn (Builder $query) => $query->whereKeyNot($package->getKey()))
                ->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $package->slug = $slug;

            if ($package->status === self::STATUS_PUBLISHED && $package->published_at === null) {
                $package->published_at = now();
            }

            if ($package->status !== self::STATUS_PUBLISHED) {
                $package->published_at = null;
            }
        });
    }

    public function images(): HasMany
    {
        return $this->hasMany(HajjOmraPackageImage::class, 'package_id')->orderBy('sort_order')->orderBy('id');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(HajjOmraDeparture::class, 'package_id')->orderBy('departure_date')->orderBy('id');
    }

    public function roomPrices(): HasMany
    {
        return $this->hasMany(HajjOmraRoomPrice::class, 'package_id')->orderBy('sort_order')->orderBy('id');
    }

    public function programDays(): HasMany
    {
        return $this->hasMany(HajjOmraProgramDay::class, 'package_id')->orderBy('day_number')->orderBy('sort_order')->orderBy('id');
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(HajjOmraBookingRequest::class, 'package_id')->orderByDesc('created_at');
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
        $today = now()->toDateString();

        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $builder) use ($today) {
                $builder
                    ->whereDate('return_date', '>=', $today)
                    ->orWhereDate('start_date', '>=', $today)
                    ->orWhereHas('departures', function (Builder $departureQuery) use ($today) {
                        $departureQuery
                            ->where('status', HajjOmraDeparture::STATUS_PUBLISHED)
                            ->whereDate('departure_date', '>=', $today);
                    });
            });
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        $type = $type !== null ? trim($type) : '';

        if ($type === '' || ! in_array($type, self::TYPES, true)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_OMRA => 'Omra',
            self::TYPE_HAJJ => 'Hajj',
            self::TYPE_RAMADAN => 'Ramadan',
            self::TYPE_LOW_COST => 'Low Cost',
            self::TYPE_PREMIUM => 'Premium',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publie',
            self::STATUS_EXPIRED => 'Expire',
            self::STATUS_FULL => 'Complet',
        ];
    }

    public static function mealPlanOptions(): array
    {
        return [
            'breakfast' => 'Petit dejeuner',
            'half_board' => 'Demi-pension',
            'full_board' => 'Pension complete',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return static::typeOptions()[$this->type] ?? Str::title(str_replace('_', ' ', (string) $this->type));
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? Str::title(str_replace('_', ' ', (string) $this->status));
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $path = (string) ($this->main_image ?? '');
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function getPriceFromValueAttribute(): ?float
    {
        $departurePrice = $this->relationLoaded('departures')
            ? $this->departures->pluck('price_from')->filter(fn ($value) => $value !== null)->min()
            : $this->departures()->whereNotNull('price_from')->min('price_from');

        $roomPrice = $this->relationLoaded('roomPrices')
            ? $this->roomPrices->pluck('price')->filter(fn ($value) => $value !== null)->min()
            : $this->roomPrices()->min('price');

        $candidates = array_filter([
            $departurePrice !== null ? (float) $departurePrice : null,
            $roomPrice !== null ? (float) $roomPrice : null,
            $this->adult_price !== null ? (float) $this->adult_price : null,
        ], static fn ($value) => $value !== null);

        if ($candidates === []) {
            return null;
        }

        return (float) min($candidates);
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

        if ($days > 0) {
            return sprintf('%d jours', $days);
        }

        return sprintf('%d nuits', $nights);
    }

    public function resolveUpcomingDeparture(): ?HajjOmraDeparture
    {
        $today = now()->toDateString();

        if ($this->relationLoaded('departures')) {
            return $this->departures
                ->filter(fn (HajjOmraDeparture $departure) => $departure->departure_date?->toDateString() >= $today)
                ->sortBy('departure_date')
                ->first();
        }

        return $this->departures()
            ->whereDate('departure_date', '>=', $today)
            ->orderBy('departure_date')
            ->first();
    }
}
