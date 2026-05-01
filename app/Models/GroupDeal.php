<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GroupDeal extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_GUARANTEED = 'guaranteed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CLOSED,
        self::STATUS_GUARANTEED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'title',
        'slug',
        'destination',
        'country',
        'city',
        'description',
        'short_description',
        'start_date',
        'end_date',
        'departure_date',
        'return_date',
        'duration_days',
        'duration_nights',
        'min_participants',
        'max_participants',
        'current_participants',
        'starting_price',
        'current_price',
        'discount_percent',
        'status',
        'badge_label',
        'registration_deadline',
        'image',
        'images',
        'program',
        'conditions',
        'services_included',
        'services_excluded',
        'share_enabled',
        'is_featured',
        'is_active',
        'sort_order',
        'guaranteed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'departure_date' => 'date',
        'return_date' => 'date',
        'registration_deadline' => 'date',
        'duration_days' => 'integer',
        'duration_nights' => 'integer',
        'min_participants' => 'integer',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'starting_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'images' => 'array',
        'services_included' => 'array',
        'services_excluded' => 'array',
        'share_enabled' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'guaranteed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (GroupDeal $deal): void {
            if (blank($deal->slug) && filled($deal->title)) {
                $deal->slug = Str::slug($deal->title);
            }

            $departureDate = $deal->departure_date ?: $deal->start_date;
            $returnDate = $deal->return_date ?: $deal->end_date;

            $deal->departure_date = $departureDate;
            $deal->return_date = $returnDate;
            $deal->start_date = $departureDate;
            $deal->end_date = $returnDate;

            if (blank($deal->short_description) && filled($deal->description)) {
                $deal->short_description = Str::limit(strip_tags((string) $deal->description), 220, '');
            }
        });
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(GroupDealPricingTier::class)->orderBy('sort_order')->orderBy('min_people');
    }

    public function pricingTiers(): HasMany
    {
        return $this->priceTiers();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupDealParticipant::class)->orderByDesc('created_at');
    }

    public function services(): HasMany
    {
        return $this->hasMany(GroupDealServiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(GroupDealCategory::class, 'group_deal_category_group_deal')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function activePricingTier(?int $count = null): ?GroupDealPricingTier
    {
        $count ??= max(0, (int) $this->current_participants);

        $tiers = $this->relationLoaded('priceTiers')
            ? $this->priceTiers
            : ($this->relationLoaded('pricingTiers') ? $this->pricingTiers : null);

        if ($tiers !== null) {
            return $tiers
                ->filter(fn (GroupDealPricingTier $tier) => $tier->min_people <= $count && ($tier->max_people === null || $tier->max_people >= $count))
                ->sortByDesc('min_people')
                ->first();
        }

        return $this->priceTiers()
            ->where('min_people', '<=', $count)
            ->where(function ($query) use ($count) {
                $query->whereNull('max_people')
                    ->orWhere('max_people', '>=', $count);
            })
            ->orderByDesc('min_people')
            ->first();
    }

    public function bestPricingTier(): ?GroupDealPricingTier
    {
        $tiers = $this->relationLoaded('priceTiers')
            ? $this->priceTiers
            : ($this->relationLoaded('pricingTiers') ? $this->pricingTiers : null);

        if ($tiers !== null) {
            return $tiers->sortBy('min_people')->last();
        }

        return $this->priceTiers()->orderBy('min_people')->orderBy('sort_order')->get()->last();
    }

    public function nextPricingTier(?int $count = null): ?GroupDealPricingTier
    {
        $count ??= max(0, (int) $this->current_participants);

        $tiers = $this->relationLoaded('priceTiers')
            ? $this->priceTiers
            : ($this->relationLoaded('pricingTiers') ? $this->pricingTiers : null);

        if ($tiers !== null) {
            return $tiers
                ->filter(fn (GroupDealPricingTier $tier) => $tier->min_people > $count)
                ->sortBy('min_people')
                ->first();
        }

        return $this->priceTiers()
            ->where('min_people', '>', $count)
            ->orderBy('min_people')
            ->first();
    }

    public function getRemainingToGuaranteeAttribute(): int
    {
        return max(0, (int) $this->min_participants - (int) $this->current_participants);
    }

    public function getRemainingPlacesAttribute(): int
    {
        return max(0, (int) $this->max_participants - (int) $this->current_participants);
    }

    public function getProgressPercentAttribute(): int
    {
        $capacity = max(1, (int) $this->max_participants);

        return (int) min(100, round(((int) $this->current_participants / $capacity) * 100));
    }

    public function getIsGuaranteedAttribute(): bool
    {
        return (int) $this->current_participants >= (int) $this->min_participants
            || $this->status === self::STATUS_GUARANTEED;
    }

    public function getStatusLabelAttribute(): string
    {
        if (filled($this->badge_label)) {
            return (string) $this->badge_label;
        }

        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publie',
            self::STATUS_CLOSED => 'Ferme',
            self::STATUS_GUARANTEED => 'Voyage garanti',
            self::STATUS_CANCELLED => 'Annule',
            default => ucfirst((string) $this->status),
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image;
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    public function getDurationLabelAttribute(): ?string
    {
        if (! $this->duration_days && ! $this->duration_nights) {
            return null;
        }

        if ($this->duration_days && $this->duration_nights) {
            return sprintf('%d jours / %d nuits', $this->duration_days, $this->duration_nights);
        }

        if ($this->duration_days) {
            return sprintf('%d jours', $this->duration_days);
        }

        return sprintf('%d nuits', $this->duration_nights);
    }

    public function getIncludedServicesAttribute(): array
    {
        if ($this->relationLoaded('services')) {
            return $this->services
                ->where('type', GroupDealServiceItem::TYPE_INCLUDED)
                ->pluck('name')
                ->values()
                ->all();
        }

        return (array) ($this->services_included ?? []);
    }

    public function getExcludedServicesAttribute(): array
    {
        if ($this->relationLoaded('services')) {
            return $this->services
                ->where('type', GroupDealServiceItem::TYPE_NOT_INCLUDED)
                ->pluck('name')
                ->values()
                ->all();
        }

        return (array) ($this->services_excluded ?? []);
    }
}
