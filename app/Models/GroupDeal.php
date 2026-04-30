<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'description',
        'start_date',
        'end_date',
        'min_participants',
        'max_participants',
        'current_participants',
        'status',
        'registration_deadline',
        'image',
        'images',
        'program',
        'services_included',
        'services_excluded',
        'share_enabled',
        'current_price',
        'guaranteed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
        'min_participants' => 'integer',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'images' => 'array',
        'services_included' => 'array',
        'services_excluded' => 'array',
        'share_enabled' => 'boolean',
        'current_price' => 'decimal:2',
        'guaranteed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (GroupDeal $deal): void {
            if (blank($deal->slug) && filled($deal->title)) {
                $deal->slug = Str::slug($deal->title);
            }
        });
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(GroupDealPricingTier::class)->orderBy('sort_order')->orderBy('min_participants');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupDealParticipant::class)->orderByDesc('created_at');
    }

    public function activePricingTier(?int $count = null): ?GroupDealPricingTier
    {
        $count ??= max(0, (int) $this->current_participants);

        if ($this->relationLoaded('pricingTiers')) {
            return $this->pricingTiers
                ->filter(fn (GroupDealPricingTier $tier) => $tier->min_participants <= $count && ($tier->max_people === null || $tier->max_people >= $count))
                ->sortByDesc('min_participants')
                ->first();
        }

        return $this->pricingTiers()
            ->where('min_participants', '<=', $count)
            ->where(function ($query) use ($count) {
                $query->whereNull('max_people')
                    ->orWhere('max_people', '>=', $count);
            })
            ->orderByDesc('min_participants')
            ->first();
    }

    public function bestPricingTier(): ?GroupDealPricingTier
    {
        if ($this->relationLoaded('pricingTiers')) {
            return $this->pricingTiers->sortBy('min_participants')->last();
        }

        return $this->pricingTiers()->orderBy('min_participants')->orderBy('sort_order')->get()->last();
    }

    public function nextPricingTier(?int $count = null): ?GroupDealPricingTier
    {
        $count ??= max(0, (int) $this->current_participants);

        if ($this->relationLoaded('pricingTiers')) {
            return $this->pricingTiers
                ->filter(fn (GroupDealPricingTier $tier) => $tier->min_participants > $count)
                ->sortBy('min_participants')
                ->first();
        }

        return $this->pricingTiers()
            ->where('min_participants', '>', $count)
            ->orderBy('min_participants')
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
        $threshold = max(1, (int) $this->min_participants);

        return (int) min(100, round(((int) $this->current_participants / $threshold) * 100));
    }

    public function getIsGuaranteedAttribute(): bool
    {
        return (int) $this->current_participants >= (int) $this->min_participants
            || $this->status === self::STATUS_GUARANTEED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publié',
            self::STATUS_CLOSED => 'Fermé',
            self::STATUS_GUARANTEED => 'Voyage garanti',
            self::STATUS_CANCELLED => 'Annulé',
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
}
